<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobOpening;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class OnboardingManagementController extends Controller
{
    // Interviews can only be booked inside this window (matches the form hint)
    private const INTERVIEW_FROM = '08:00';
    private const INTERVIEW_TO   = '14:00';

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'archive' ? 'archive' : 'active';

        // Active -> pending | scheduled, Archive -> approved | rejected
        $allowed = $tab === 'archive' ? ['approved', 'rejected'] : ['pending', 'scheduled'];
        $status  = $request->query('status', '');
        $status  = in_array($status, $allowed, true) ? $status : '';

        $search = trim((string) $request->query('q', ''));

        // Stat cards (not affected by search/filter)
        $total    = Application::count();
        $approved = Application::statusKey('approved')->count();
        $rejected = Application::statusKey('rejected')->count();

        $stats = [
            'total'        => $total,
            'under_review' => $total - $approved - $rejected,
            'approved'     => $approved,
            'rejected'     => $rejected,
        ];

        $counts = [
            'active'  => $total - $approved - $rejected,
            'archive' => $approved + $rejected,
        ];

        $applicantCounts = Application::query()
            ->selectRaw('position, COUNT(*) as applicants')
            ->groupBy('position')
            ->pluck('applicants', 'position');

        $jobOpenings = JobOpening::query()
            ->orderBy('title')
            ->get()
            ->map(fn (JobOpening $opening) => [
                'id'         => $opening->id,
                'title'      => $opening->title,
                'applicants' => (int) ($applicantCounts[$opening->title] ?? 0),
            ]);

        $applications = Application::query()
            ->archived($tab === 'archive')
            ->search($search)
            ->statusKey($status)
            ->orderByDesc($tab === 'archive' ? 'updated_at' : 'created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $disk = Storage::disk(Application::DOCUMENT_DISK);

        // Data for the Alpine modal, keyed by application id
        $cards = $applications->getCollection()->mapWithKeys(fn (Application $a) => [
            $a->id => [
                'id'                 => $a->id,
                'code'               => $a->code,
                'full_name'          => $a->fullname,
                'position'           => $a->position,
                'job_type'           => $a->job_type ?: 'N/A',
                'email'              => $a->email,
                'phone'              => $a->phone ?: 'N/A',
                'address'            => $a->home_address ?: 'N/A',
                'birth_date'         => $a->birth_date?->format('M d, Y') ?? 'N/A',
                'gender'             => $a->gender ?: 'N/A',
                'civil_status'       => $a->civil_status ?: 'N/A',
                'education'          => $a->education ?: 'N/A',
                'submitted_at'       => $a->created_at?->format('M d, Y h:i A') ?? 'N/A',

                // Interview: display values
                'interview_date'     => $a->interview_date?->format('M d, Y') ?? 'Not scheduled',
                'interview_time'     => $a->interview_time ?: 'N/A',
                'interview_location' => $a->interview_location ?: 'N/A',
                'interview_note'     => $a->interview_note ?? '',

                // Interview: raw values that prefill the schedule form
                'interview_date_value' => $a->interview_date?->toDateString() ?? '',
                'interview_time_value' => ($a->interview_at && $a->interview_time) ? $a->interview_at->format('H:i') : '',

                'rejection_reason'   => $a->rejection_reason,
                'status'             => $a->workflow_status,
                'status_key'         => $a->status_key,
                'can_schedule'       => ! $a->is_archived,
                'can_decide'         => $a->can_decide,
                'schedule_url'       => route('onboarding.schedule', $a),
                'decide_url'         => route('onboarding.decide', $a),
                'documents'          => collect(Application::DOCUMENTS)->map(function ($meta, $type) use ($a, $disk) {
                    $path   = $a->documentPath($type);
                    $exists = $path && $disk->exists($path);

                    return [
                        'label' => $meta['label'],
                        'name'  => $exists ? basename($path) : null,
                        'url'   => $exists ? route('onboarding.document', [$a, $type]) : null,
                    ];
                })->values()->all(),
            ],
        ]);

        return view('onboarding', compact('applications', 'cards', 'stats', 'counts', 'jobOpenings', 'search', 'status', 'tab'));
    }

    /**
     * Set or change the interview schedule (date, time, location, note).
     * Not allowed once the application is approved/rejected.
     */
    public function schedule(Request $request, Application $application): RedirectResponse
    {
        if ($application->is_archived) {
            return $this->backToModal($application)->withErrors([
                'interview_date' => 'This application is already closed, so it can no longer be scheduled.',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'interview_date'     => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'interview_time'     => ['required', 'date_format:H:i'],
            'interview_location' => ['nullable', 'string', 'max:255'],
            'interview_note'     => ['nullable', 'string', 'max:1000'],
        ], [
            'interview_date.after_or_equal' => 'The interview date cannot be in the past.',
            'interview_time.date_format'    => 'Please enter a valid interview time.',
        ]);

        if ($validator->fails()) {
            return $this->backToModal($application)->withErrors($validator);
        }

        $data = $validator->validated();
        $at   = Carbon::createFromFormat('Y-m-d H:i', $data['interview_date'] . ' ' . $data['interview_time']);

        // Office window: 8:00 AM - 2:00 PM
        if ($at->format('H:i') < self::INTERVIEW_FROM || $at->format('H:i') > self::INTERVIEW_TO) {
            return $this->backToModal($application)->withErrors([
                'interview_time' => 'Interviews can only be scheduled between 8:00 AM and 2:00 PM.',
            ]);
        }

        if ($at->isPast()) {
            return $this->backToModal($application)->withErrors([
                'interview_time' => 'Please pick a time that has not passed yet.',
            ]);
        }

        $wasScheduled = (bool) $application->interview_date;

        $application->update([
            'interview_date'     => $at->toDateString(),
            'interview_time'     => $at->format('g:i A'), // shown as-is on the applicant's Step 3
            'interview_location' => filled($data['interview_location'] ?? null) ? trim($data['interview_location']) : null,
            'interview_note'     => filled($data['interview_note'] ?? null) ? trim($data['interview_note']) : null,
        ]);

        return $this->backToModal($application)->with(
            'success',
            'Interview ' . ($wasScheduled ? 'rescheduled' : 'scheduled') . ' for ' . $at->format('F j, Y \a\t g:i A') . '.'
        );
    }

    /**
     * Approve (status = qualified) / Reject (status = rejected + reason).
     * Once decided, the application moves to the Archive tab automatically.
     */
    public function decide(Request $request, Application $application): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason'   => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
        ], [
            'reason.required_if' => 'Please enter a reason for rejecting this application.',
        ]);

        if (! $application->can_decide) {
            return $this->backToModal($application)->withErrors([
                'decision' => 'This application can only be decided once the interview is done, and only once.',
            ]);
        }

        if ($data['decision'] === 'approved') {
            $application->update([
                'status'           => 'qualified',
                'rejection_reason' => null,
            ]);
        } else {
            $application->update([
                'status'           => 'rejected',
                'rejection_reason' => trim($data['reason']),
            ]);
        }

        // Decided applications leave the Active tab, so don't reopen the modal here
        return back()->with('success', 'Application ' . $data['decision'] . ' and moved to Archive.');
    }

    public function destroy(Application $application): RedirectResponse
    {
        $disk = Storage::disk(Application::DOCUMENT_DISK);

        foreach (array_keys(Application::DOCUMENTS) as $type) {
            if ($path = $application->documentPath($type)) {
                $disk->delete($path);
            }
        }

        $application->delete();

        return back()->with('success', 'Applicant deleted.');
    }

    public function document(Application $application, string $type)
    {
        abort_unless(array_key_exists($type, Application::DOCUMENTS), 404);

        $path = $application->documentPath($type);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk(Application::DOCUMENT_DISK);

        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, basename($path));
    }

    // Go back to the list and tell the page to reopen this applicant's modal
    private function backToModal(Application $application): RedirectResponse
    {
        return back()->with('open_application', $application->id);
    }
}
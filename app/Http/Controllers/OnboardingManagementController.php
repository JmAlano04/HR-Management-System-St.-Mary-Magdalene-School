<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OnboardingManagementController extends Controller
{
    /**
     * Onboarding Dashboard — stats, search/filter, applications table + modal data.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status', '');
        $status = in_array($status, ['pending', 'approved', 'rejected', 'scheduled'], true) ? $status : '';

        // Stat cards (hindi apektado ng search/filter)
        $total    = Application::count();
        $approved = Application::statusKey('approved')->count();
        $rejected = Application::statusKey('rejected')->count();

        $stats = [
            'total'        => $total,
            'under_review' => $total - $approved - $rejected,
            'approved'     => $approved,
            'rejected'     => $rejected,
        ];

        $applications = Application::query()
            ->search($search)
            ->statusKey($status)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $disk = Storage::disk(Application::DOCUMENT_DISK);

        // Data para sa Alpine modal, keyed by application id
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
                'interview_date'     => $a->interview_date?->format('M d, Y') ?? 'Not scheduled',
                'interview_time'     => $a->interview_time ?: 'N/A',
                'interview_location' => $a->interview_location ?: 'N/A',
                'rejection_reason'   => $a->rejection_reason,
                'status'             => $a->workflow_status,
                'status_key'         => $a->status_key,
                'can_decide'         => $a->can_decide,
                'decide_url'         => route('onboarding.decide', $a),
                'documents'          => collect(Application::DOCUMENTS)->map(function ($meta, $type) use ($a, $disk) {
                    $path = $a->documentPath($type);
                    $exists = $path && $disk->exists($path);

                    return [
                        'label' => $meta['label'],
                        'name'  => $exists ? basename($path) : null,
                        'url'   => $exists ? route('onboarding.document', [$a, $type]) : null,
                    ];
                })->values()->all(),
            ],
        ]);

        return view('onboarding', compact('applications', 'cards', 'stats', 'search', 'status'));
    }

    /**
     * Admin Approve (status = qualified) / Reject (status = rejected + reason).
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
            return back()->withErrors([
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

        return back()->with('success', 'Application ' . $data['decision'] . '.');
    }

    /**
     * Delete an application (and its uploaded documents).
     */
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

    /**
     * Open an uploaded document.
     */
    public function document(Application $application, string $type)
    {
        abort_unless(array_key_exists($type, Application::DOCUMENTS), 404);

        $path = $application->documentPath($type);
        $disk = Storage::disk(Application::DOCUMENT_DISK);

        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, basename($path));
    }
}
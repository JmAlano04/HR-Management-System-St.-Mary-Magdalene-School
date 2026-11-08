<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Models\OffboardingCase;
use App\Models\OffboardingDocument;
use App\Services\HrmsNotifier;
=======
use App\Http\Controllers\Controller;
use App\Models\OffboardingCase;
use App\Models\OffboardingDocument;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OffboardingController extends Controller
{
    /**
     * Offboarding Dashboard — HR-approved cases + modal data.
     */
<<<<<<< HEAD
    public function index(Request $request): View
    {
        $isHr = auth()->user()->hasRole('HR');
        $validated = $request->validate([
            'tab' => ['sometimes', 'in:current,archive'],
            'employee' => ['nullable', 'string', 'max:100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,'.now()->year],
        ]);
        $tab = $validated['tab'] ?? 'current';
        $employeeFilter = trim($validated['employee'] ?? '');
        $monthFilter = $validated['month'] ?? '';
        $yearFilter = $validated['year'] ?? '';

        $caseQuery = OffboardingCase::query();

        if ($isHr) {
            $archiveCases = fn ($query) => $query
                ->where('admin_final_approved', true)
                ->orWhere('status', 'Rejected');
            $stats = [
                'pending' => (clone $caseQuery)
                    ->where('admin_final_approved', false)
                    ->where('status', '!=', 'Rejected')
                    ->count(),
                'completed' => (clone $caseQuery)->where($archiveCases)->count(),
            ];
            $caseQuery->where(function ($query) use ($tab, $archiveCases) {
                if ($tab === 'archive') {
                    $query->where($archiveCases);
                } else {
                    $query->where('admin_final_approved', false)
                        ->where('status', '!=', 'Rejected');
                }
            });
        } else {
            $stats = [
                'pending' => (clone $caseQuery)->sentToAdmin()
                    ->where('admin_final_approved', false)
                    ->where('status', '!=', 'Rejected')
                    ->count(),
                'completed' => (clone $caseQuery)->sentToAdmin()
                    ->where(function ($query) {
                        $query->where('admin_final_approved', true)
                            ->orWhere('status', 'Rejected');
                    })->count(),
            ];
            $caseQuery->sentToAdmin()
                ->where(function ($query) use ($tab) {
                    if ($tab === 'archive') {
                        $query->where('admin_final_approved', true)
                            ->orWhere('status', 'Rejected');
                    } else {
                        $query->where('admin_final_approved', false)
                            ->where('status', '!=', 'Rejected');
                    }
                });
        }

        if ($tab === 'archive') {
            if ($employeeFilter !== '') {
                $caseQuery->whereHas('employee', fn ($query) => $query
                    ->where('name', 'like', '%'.$employeeFilter.'%'));
            }

            if ($monthFilter !== '' || $yearFilter !== '') {
                $caseQuery->where(function ($query) use ($monthFilter, $yearFilter) {
                    $query->where(function ($approved) use ($monthFilter, $yearFilter) {
                        $approved->where('admin_final_approved', true);
                        if ($monthFilter !== '') {
                            $approved->whereMonth('admin_approved_at', $monthFilter);
                        }
                        if ($yearFilter !== '') {
                            $approved->whereYear('admin_approved_at', $yearFilter);
                        }
                    })->orWhere(function ($rejected) use ($monthFilter, $yearFilter) {
                        $rejected->where('status', 'Rejected');
                        if ($monthFilter !== '') {
                            $rejected->whereMonth('updated_at', $monthFilter);
                        }
                        if ($yearFilter !== '') {
                            $rejected->whereYear('updated_at', $yearFilter);
                        }
                    });
                });
            }
        }

        $cases = $caseQuery
            ->with(['employee', 'documents'])
            ->orderBy('admin_final_approved')
            ->orderByDesc($isHr ? 'created_at' : 'sent_to_admin_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
=======
    public function index(): View
    {
        $stats = [
            'pending'   => OffboardingCase::sentToAdmin()->where('admin_final_approved', false)->count(),
            'completed' => OffboardingCase::sentToAdmin()->where('admin_final_approved', true)->count(),
        ];

        $cases = OffboardingCase::query()
            ->sentToAdmin()
            ->with(['employee', 'documents'])
            ->orderBy('admin_final_approved')          // awaiting first
            ->orderByDesc('sent_to_admin_at')
            ->orderByDesc('id')
            ->paginate(10);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        $disk = Storage::disk(OffboardingCase::DOCUMENT_DISK);

        // Data para sa Alpine modal, keyed by case id
        $cards = $cases->getCollection()->mapWithKeys(fn (OffboardingCase $c) => [
            $c->id => [
<<<<<<< HEAD
                'id' => $c->id,
                'employee_name' => $c->employee?->name ?? 'Unknown employee',
                'position' => $c->employee?->position ?? 'N/A',
                'reason' => $c->reason,
                'last_working_day' => $c->last_working_day->format('M d, Y'),
                'message' => $c->employee_message ?: 'No additional message from the employee.',
                'approved' => $c->admin_final_approved,
                'can_approve' => $c->can_final_approve,
                'can_decide' => $c->sent_to_admin
                    && ! $c->admin_final_approved
                    && $c->status !== 'Rejected',
                'can_hr_reject' => $isHr
                    && ! $c->sent_to_admin
                    && $c->status === 'Open',
                'status_message' => match (true) {
                    ! $isHr => $c->status_message,
                    $c->status === 'Rejected' => 'This request was rejected.',
                    $c->sent_to_admin => 'This request has been forwarded to Admin for final review.',
                    ! $c->requirementsSent() => 'This employee request is awaiting HR review.',
                    $c->can_forward_to_admin => 'All requirements are approved and ready for Admin.',
                    $c->submitted_required_document_count > 0 => 'Employee documents are submitted and ready for HR review.',
                    default => 'Requirements sent; waiting for employee documents.',
                },
                'approve_url' => route('offboarding.approve', $c),
                'reject_url' => route('offboarding.reject', $c),
                'requirements_url' => route('offboarding.requirements', $c),
                'forward_url' => route('offboarding.forward', $c),
                'can_forward' => $c->can_forward_to_admin,
                'is_current' => ! $c->sent_to_admin,
                'requirements_sent' => $c->requirementsSent(),
                'requirements_text' => implode("\n", $c->required_document_labels),
                'interviews' => collect($c->required_document_labels)
                    ->intersect(OffboardingCase::INTERVIEW_LABELS)
                    ->map(fn (string $label) => [
                        'label' => $label,
                        'done' => in_array($label, $c->completed_interviews ?? [], true),
                        'url' => route('offboarding.interviews.complete', $c),
                    ])
                    ->values()
                    ->all(),
                'is_hr' => $isHr,
                'documents' => $isHr
                    ? collect($c->uploadable_document_labels)->map(function (string $label) use ($c, $disk) {
                        $document = $c->documents->firstWhere('document_label', $label);

                        return [
                            'id' => $document?->id,
                            'label' => $label,
                            'name' => $document?->file_name,
                            'status' => $document?->review_status ?? 'Awaiting employee upload',
                            'rejection_reason' => $document?->rejection_reason,
                            'hr_ok' => $document?->review_status === 'Approved',
                            'url' => $document && $disk->exists($document->file_path)
                                ? route('offboarding.document', [$c, $document])
                                : null,
                            'review_url' => $document ? route('offboarding.documents.review', [$c, $document]) : null,
                        ];
                    })->values()->all()
                    : $c->documents->map(fn (OffboardingDocument $d) => [
                        'id' => $d->id,
                        'label' => $d->document_label ?: $d->file_name,
                        'name' => $d->file_name,
                        'status' => $d->review_status,
                        'hr_ok' => $d->review_status === 'Approved',
                        'url' => $disk->exists($d->file_path)
                            ? route('offboarding.document', [$c, $d])
                            : null,
                    ])->values()->all(),
            ],
        ]);

        return view('offboarding', compact(
            'cases',
            'cards',
            'stats',
            'tab',
            'employeeFilter',
            'monthFilter',
            'yearFilter',
        ));
=======
                'id'                => $c->id,
                'employee_name'     => $c->employee?->name ?? 'Unknown employee',
                'position'          => $c->employee?->position ?? 'N/A',
                'reason'            => $c->reason,
                'last_working_day'  => $c->last_working_day->format('M d, Y'),
                'message'           => $c->employee_message ?: 'No additional message from the employee.',
                'approved'          => $c->admin_final_approved,
                'can_approve'       => $c->can_final_approve,
                'status_message'    => $c->status_message,
                'approve_url'       => route('offboarding.approve', $c),
                'documents'         => $c->documents->map(fn (OffboardingDocument $d) => [
                    'id'     => $d->id,
                    'label'  => $d->document_label ?: $d->file_name,
                    'name'   => $d->file_name,
                    'status' => $d->review_status,
                    'hr_ok'  => $d->review_status === 'Approved',
                    'url'    => $disk->exists($d->file_path)
                        ? route('offboarding.document', [$c, $d])
                        : null,
                ])->values()->all(),
            ],
        ]);

        return view('offboarding', compact('cases', 'cards', 'stats'));
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    /**
     * Final approval — only after HR approved every submitted document.
     */
    public function approve(Request $request, OffboardingCase $offboardingCase): RedirectResponse
    {
<<<<<<< HEAD
        abort_unless($request->user()->hasRole('Admin'), 403);
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $offboardingCase->load('documents');

        if (! $offboardingCase->can_final_approve) {
            return back()->withErrors([
                'approve' => 'Final approval is available only after HR approves every submitted document.',
            ]);
        }

        $offboardingCase->update([
            'admin_final_approved' => true,
<<<<<<< HEAD
            'admin_approved_at' => now(),
            'admin_approved_by' => $request->user()?->id,
            'status' => 'Closed',
            'progress' => 100,
        ]);

        HrmsNotifier::offboardingAdminDecided($offboardingCase, true);

        return redirect()->route('offboarding.index', ['tab' => 'archive'])
            ->with('success', 'Final approval completed.')
            ->with('offboarding_detail_id', $offboardingCase->id);
    }

    public function reject(Request $request, OffboardingCase $offboardingCase): RedirectResponse
    {
        $isHrRejection = $request->user()->hasRole('HR')
            && ! $offboardingCase->sent_to_admin
            && $offboardingCase->status === 'Open';
        $isAdminRejection = $request->user()->hasRole('Admin')
            && $offboardingCase->sent_to_admin
            && ! $offboardingCase->admin_final_approved
            && $offboardingCase->status !== 'Rejected';
        abort_unless($isHrRejection || $isAdminRejection, 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $reason = trim($data['reason']);
        if ($reason === '') {
            return back()->withErrors(['reason' => 'A rejection reason is required.'])->withInput();
        }

        $offboardingCase->update([
            'status' => 'Rejected',
            'rejection_reason' => $reason,
        ]);
        if ($isHrRejection) {
            HrmsNotifier::offboardingHrRejected($offboardingCase, $reason);
        } else {
            HrmsNotifier::offboardingAdminDecided($offboardingCase, false, $reason);
        }

        return redirect()->route('offboarding.index', ['tab' => 'archive'])
            ->with('success', 'Offboarding request rejected.')
            ->with('offboarding_detail_id', $offboardingCase->id);
    }

    public function sendRequirements(Request $request, OffboardingCase $offboardingCase): RedirectResponse
    {
        abort_unless($request->user()->hasRole('HR'), 403);
        abort_if($offboardingCase->sent_to_admin || $offboardingCase->status !== 'Open', 403);

        if ($offboardingCase->requirementsSent()) {
            return back()->withErrors(['requirements' => 'Requirements have already been sent to this employee.']);
        }

        $data = $request->validate([
            'requirements_text' => ['required', 'string', 'max:2000'],
        ]);
        $labels = array_values(array_unique(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', $data['requirements_text']) ?: []
        ))));

        if (! $labels || array_diff($labels, OffboardingCase::REQUIRED_DOCUMENTS)) {
            return back()->withErrors(['requirements' => 'Choose valid offboarding requirements, one per line.']);
        }

        if (array_diff($labels, OffboardingCase::INTERVIEW_LABELS) === []) {
            return back()->withErrors(['requirements' => 'At least one document upload requirement is needed.']);
        }

        $offboardingCase->update([
            'required_documents' => $labels,
            'requirements_sent_at' => now(),
        ]);
        $offboardingCase->refreshProgress();

        HrmsNotifier::offboardingRequirementsSent($offboardingCase);

        return back()
            ->with('success', 'Document requirements sent to the employee.')
            ->with('offboarding_detail_id', $offboardingCase->id);
    }

    public function completeInterview(Request $request, OffboardingCase $offboardingCase): RedirectResponse
    {
        abort_unless($request->user()->hasRole('HR'), 403);
        abort_if($offboardingCase->sent_to_admin || ! $offboardingCase->requirementsSent(), 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'in:'.implode(',', OffboardingCase::INTERVIEW_LABELS)],
        ]);
        abort_unless(in_array($data['label'], $offboardingCase->required_document_labels, true), 422);

        $completed = $offboardingCase->completed_interviews ?? [];
        $alreadyCompleted = in_array($data['label'], $completed, true);
        $completed[] = $data['label'];
        $offboardingCase->update(['completed_interviews' => array_values(array_unique($completed))]);
        $offboardingCase->refreshProgress();

        if (! $alreadyCompleted) {
            HrmsNotifier::offboardingInterviewCompleted($offboardingCase, $data['label']);
        }

        return back()
            ->with('success', 'Interview clearance marked done.')
            ->with('offboarding_detail_id', $offboardingCase->id);
    }

    public function reviewDocument(Request $request, OffboardingCase $offboardingCase, OffboardingDocument $document): RedirectResponse
    {
        abort_unless($request->user()->hasRole('HR'), 403);
        abort_unless(
            ! $offboardingCase->sent_to_admin
                && $offboardingCase->requirementsSent()
                && $document->offboarding_case_id === $offboardingCase->id
                && in_array($document->document_label, $offboardingCase->uploadable_document_labels, true),
            404
        );

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
        ]);

        $approved = $data['decision'] === 'approved';
        $document->update([
            'review_status' => $approved ? 'Approved' : 'Rejected',
            'rejection_reason' => $approved ? null : trim($data['reason']),
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);
        $offboardingCase->load('documents')->refreshProgress();

        HrmsNotifier::offboardingDocumentReviewed($document, $approved);

        return back()
            ->with('success', $approved ? 'Document approved.' : 'Document rejected with instructions for replacement.')
            ->with('offboarding_detail_id', $offboardingCase->id);
    }

    public function forwardToAdmin(Request $request, OffboardingCase $offboardingCase): RedirectResponse
    {
        abort_unless($request->user()->hasRole('HR'), 403);
        $offboardingCase->load('documents');

        if (! $offboardingCase->can_forward_to_admin) {
            return back()->withErrors([
                'forward' => 'Approve every required document and complete each interview clearance before sending to Admin.',
            ]);
        }

        $offboardingCase->update([
            'sent_to_admin' => true,
            'sent_to_admin_at' => now(),
            'progress' => 100,
        ]);
        HrmsNotifier::offboardingSentToAdmin($offboardingCase);

        return back()
            ->with('success', 'Offboarding request sent to Admin for final approval.')
            ->with('offboarding_detail_id', $offboardingCase->id);
=======
            'admin_approved_at'    => now(),
            'admin_approved_by'    => $request->user()?->id,
            'status'               => 'Closed',
            'progress'             => 100,
        ]);

        return back()->with('success', 'Final approval completed.');
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    /**
     * Open a submitted document (private disk).
     */
    public function document(OffboardingCase $offboardingCase, OffboardingDocument $document)
    {
        abort_unless(
<<<<<<< HEAD
            (auth()->user()->hasRole('HR') || $offboardingCase->sent_to_admin)
                && $document->offboarding_case_id === $offboardingCase->id,
=======
            $offboardingCase->sent_to_admin && $document->offboarding_case_id === $offboardingCase->id,
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            404
        );

        $disk = Storage::disk(OffboardingCase::DOCUMENT_DISK);

        abort_unless($disk->exists($document->file_path), 404);

        $contentType = 'application/octet-stream';

        if (method_exists($disk, 'mimeType')) {
            $contentType = $disk->mimeType($document->file_path) ?: $contentType;
        } elseif (method_exists($disk, 'path')) {
            $localPath = $disk->path($document->file_path);

            if (is_file($localPath) && function_exists('mime_content_type')) {
                $contentType = mime_content_type($localPath) ?: $contentType;
            }
        }

        return response()->stream(function () use ($disk, $document): void {
            $stream = $disk->readStream($document->file_path);

            if ($stream !== false) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $contentType,
<<<<<<< HEAD
            'Content-Disposition' => 'inline; filename="'.addslashes($document->file_name).'"',
        ]);
    }
}
=======
            'Content-Disposition' => 'inline; filename="' . addslashes($document->file_name) . '"',
        ]);
    }
}
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

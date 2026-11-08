<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OffboardingCase;
use App\Services\HrmsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployeeOffboardingController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $this->employee($request);

        $case = OffboardingCase::where('employee_id', $employee->id)
            ->with('documents')
            ->latest()
            ->first();

        return view('offboarding', [
            'employee' => $employee,
            'case' => $case,
            'requiredLabels' => $case?->required_document_labels ?? [],
            'requirementsSent' => $case?->requirementsSent() ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->employee($request);

        // One request per employee, ever.
        if (OffboardingCase::where('employee_id', $employee->id)->exists()) {
            return back()->withErrors([
                'last_working_day' => 'Offboarding requests can only be submitted once.',
            ]);
        }

        $data = $request->validate([
            'last_working_day' => ['required', 'date', 'after_or_equal:today'],
            'employee_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $case = OffboardingCase::create([
            'employee_id' => $employee->id,
            'reason' => 'Resignation',
            'last_working_day' => $data['last_working_day'],
            'employee_message' => $data['employee_message'] ?? null,
            'status' => 'Open',
            'progress' => 10,
        ]);

        HrmsNotifier::offboardingSubmitted($case);

        return redirect()
            ->route('offboarding.employee.index')
            ->with('success', 'Your request was sent to HR. You can track its status here.');
    }

    public function storeDocuments(Request $request, OffboardingCase $case): RedirectResponse
    {
        abort_unless($case->employee_id === $this->employee($request)->id, 403);

        if (in_array($case->status, ['Closed', 'Rejected'], true) || $case->sent_to_admin) {
            return back()->withErrors(['documents' => 'This request is no longer accepting documents.']);
        }

        if (! $case->requirementsSent()) {
            return back()->withErrors(['documents' => 'HR has not sent these requirements yet.']);
        }

        $request->validate([
            'documents' => ['required', 'array'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,doc,docx', 'max:10240'],
        ]);

        // Drop empty file inputs, then make sure every label is a real, uploadable requirement.
        $files = array_filter($request->file('documents', []));

        if (! $files) {
            return back()->withErrors(['documents' => 'Choose at least one required document before sending.']);
        }

        if (array_diff(array_keys($files), $case->uploadableDocumentLabels())) {
            return back()->withErrors(['documents' => 'One or more documents are not part of your requirements.']);
        }

        $sent = 0;
        $submittedDocuments = [];

        DB::transaction(function () use ($case, $files, &$sent, &$submittedDocuments) {
            foreach ($files as $label => $file) {
                $existing = $case->documents()->where('document_label', $label)->first();

                // Approved documents are locked.
                if ($existing?->review_status === 'Approved') {
                    continue;
                }

                $path = $file->store("offboarding/{$case->id}", OffboardingCase::DOCUMENT_DISK);

                if ($existing) {
                    Storage::disk(OffboardingCase::DOCUMENT_DISK)->delete($existing->file_path);
                }

                $document = $case->documents()->updateOrCreate(
                    ['document_label' => $label],
                    [
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'review_status' => 'Under review',
                        'rejection_reason' => null,
                        'reviewed_at' => null,
                        'reviewed_by' => null,
                    ]
                );

                $submittedDocuments[] = $document;
                $sent++;
            }

            $case->refreshProgress();
        });

        if ($sent === 0) {
            return back()->withErrors(['documents' => 'Those documents are already approved.']);
        }

        foreach ($submittedDocuments as $document) {
            HrmsNotifier::offboardingRequirementSubmitted($document);
        }

        return redirect()
            ->route('offboarding.employee.index')
            ->with('success', $sent.' '.str('document')->plural($sent).' sent to HR.');
    }

    private function employee(Request $request): Employee
    {
        $employee = $request->user()->employee;

        abort_if(! $employee, 403, 'No employee profile is linked to this account.');

        return $employee;
    }
}

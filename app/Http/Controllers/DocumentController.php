<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\SubmissionBox;
use App\Services\HrmsNotifier;
=======
use App\Models\EmployeeDocument;
use App\Models\SubmissionBox;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    /** HR document management: stats, submission boxes and the files employees uploaded. */
    public function index()
    {
<<<<<<< HEAD
        $boxes = SubmissionBox::with(['documents.employee', 'recipient'])->latest()->get();

        return view('hr-document', [
            'boxes'      => $boxes->map->toCard()->values(),
            'employees'  => Employee::query()
                ->whereNotNull('user_id')
                ->orderBy('name')
                ->get(['id', 'name', 'employee_id']),
=======
        $boxes = SubmissionBox::with('documents.employee')->latest()->get();

        return view('hr-document', [
            'boxes'      => $boxes->map->toCard()->values(),
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'stats'      => [
                'total'    => EmployeeDocument::count(),
                'approved' => EmployeeDocument::where('status', EmployeeDocument::APPROVED)->count(),
                'rejected' => EmployeeDocument::where('status', EmployeeDocument::REJECTED)->count(),
                'pending'  => EmployeeDocument::where('status', EmployeeDocument::PENDING)->count(),
            ],
            'reopenBox'  => session('reopen_box'),
        ]);
    }

    /** "Create submission box" form. */
    public function storeBox(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:150'],
            'type'         => ['required', 'string', 'max:100'],
            'deadline'     => ['nullable', 'date', 'after_or_equal:today'],
<<<<<<< HEAD
            'employee_id'  => ['nullable', 'integer', 'exists:employees,id'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $box = SubmissionBox::create($data + ['created_by' => Auth::id()]);
        $recipients = $box->employee_id
            ? collect([$box->employee_id])
            : Employee::query()->whereNotNull('user_id')->pluck('id');

        foreach ($recipients as $employeeId) {
            HrmsNotifier::documentsRequested($box, (int) $employeeId);
        }
=======
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        SubmissionBox::create($data + ['created_by' => Auth::id()]);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        return redirect()->route('documents.index')->with('success', 'Submission box created.');
    }

    /** Approve or reject an uploaded document. */
    public function decide(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'document_id' => ['required', 'integer', 'exists:employee_documents,id'],
            'decision'    => ['required', Rule::in([EmployeeDocument::APPROVED, EmployeeDocument::REJECTED])],
        ]);

        $document = EmployeeDocument::findOrFail($data['document_id']);

        if ($data['decision'] === EmployeeDocument::REJECTED) {
<<<<<<< HEAD
            HrmsNotifier::documentsReviewed($document, $data['decision']);

            // Burahin muna ang file sa storage, tapos ang record
            if ($document->file_path) {
                Storage::disk('local')->delete($document->file_path);
=======
            // Burahin muna ang file sa storage, tapos ang record
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            }

            $document->delete();
        } else {
            $document->update([
                'status'      => $data['decision'],
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
            ]);
<<<<<<< HEAD
            HrmsNotifier::documentsReviewed($document, $data['decision']);
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        }

        return redirect()->route('documents.index')
            ->with('success', 'Document ' . strtolower($data['decision']) . '.');
    }

    /** Delete an uploaded document (and its file), then reopen the box it belonged to. */
    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'document_id' => ['required', 'integer', 'exists:employee_documents,id'],
        ]);

        $document = EmployeeDocument::findOrFail($data['document_id']);

        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }

        $boxId = $document->submission_box_id;
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Document deleted.')
            ->with('reopen_box', $boxId);
    }

    /** Opens the private file in the browser (only HR can reach this route). */
    public function file(EmployeeDocument $document)
    {
        abort_unless(
            $document->file_path && Storage::disk('local')->exists($document->file_path),
            404
        );

        return response()->file(Storage::disk('local')->path($document->file_path));
    }
}
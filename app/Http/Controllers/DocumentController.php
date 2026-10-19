<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocument;
use App\Models\SubmissionBox;
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
        $boxes = SubmissionBox::with('documents.employee')->latest()->get();

        return view('hr-document', [
            'boxes'      => $boxes->map->toCard()->values(),
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
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        SubmissionBox::create($data + ['created_by' => Auth::id()]);

        return redirect()->route('documents.index')->with('success', 'Submission box created.');
    }

    /** Approve or reject an uploaded document. */
    public function decide(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'document_id' => ['required', 'integer', 'exists:employee_documents,id'],
            'decision'    => ['required', Rule::in([EmployeeDocument::APPROVED, EmployeeDocument::REJECTED])],
        ]);

        EmployeeDocument::findOrFail($data['document_id'])->update([
            'status'      => $data['decision'],
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

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
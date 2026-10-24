<?php
// app/Http/Controllers/Employee/DocumentController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeDocumentRequest;
use App\Models\EmployeeDocument;
use App\Models\SubmissionBox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    /**
     * GET /employee/documents
     * Lists active submission boxes, each with THIS employee's uploads only.
     */
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        $boxes = SubmissionBox::active()
            ->with(['documents' => fn ($q) => $q
                ->where('employee_id', $employee->id)
                ->reorder()->latest('id')])
            ->orderByRaw('deadline is null, deadline asc')
            ->get()
            ->map(function (SubmissionBox $box) {
                $latest = $box->documents->first();

                // Mirrors the JS: hide the upload button once the latest upload is approved.
                $box->can_upload = ! $box->isPastDeadline()
                    && $latest?->status !== 'approved';
                $box->has_submitted = $box->documents->isNotEmpty();

                return $box;
            });

        return view('hr-document', [
            'employee' => $employee,
            'boxes'    => $boxes,
        ]);
    }

    /**
     * GET /employee/documents/boxes/{box}
     * JSON for the "Submitted documents" modal.
     */
    public function boxDocuments(Request $request, SubmissionBox $box): JsonResponse
    {
        abort_unless($box->is_active, 404);

        $documents = $box->documents()
            ->where('employee_id', $request->user()->employee->id)
            ->reorder()->latest('id')
            ->get()
            ->map(fn (EmployeeDocument $doc) => [
                'id'             => $doc->id,
                'name'           => $doc->name,
                'type'           => $doc->type ?: $box->type,
                'size'           => $doc->size,
                'uploaded_at'    => $doc->uploaded_at,
                'status'         => $doc->status,
                'status_message' => $doc->status_message,
                'file_url'       => route('employee.documents.file', $doc),
            ]);

        return response()->json([
            'box'       => ['id' => $box->id, 'name' => $box->name, 'type' => $box->type],
            'documents' => $documents,
        ]);
    }

    /**
     * POST /employee/documents
     */
    public function store(StoreEmployeeDocumentRequest $request): RedirectResponse|JsonResponse
    {
        $employee = $request->user()->employee;
        $box      = SubmissionBox::active()->find($request->integer('submission_box_id'));

        // Same "no longer available" guard as the JS.
        if (! $box) {
            throw ValidationException::withMessages([
                'submission_box_id' => 'This submission box is no longer available. Please choose another one.',
            ]);
        }

        if ($box->isPastDeadline()) {
            throw ValidationException::withMessages([
                'submission_box_id' => 'The deadline for this submission box has passed.',
            ]);
        }

        // Once approved, no more uploads (the JS hides the button; enforce it server-side too).
        $alreadyApproved = EmployeeDocument::where('employee_id', $employee->id)
            ->where('submission_box_id', $box->id)
            ->where('status', 'approved')
            ->exists();

        if ($alreadyApproved) {
            throw ValidationException::withMessages([
                'submission_box_id' => 'Your document for this box has already been approved.',
            ]);
        }

        $file = $request->file('file');

        // Private disk (storage/app/private) — never publicly reachable.
        $path = $file->store("employee-documents/{$employee->id}", 'local');

        $document = EmployeeDocument::create([
            'submission_box_id' => $box->id,
            'employee_id'       => $employee->id,
            'name'              => $file->getClientOriginalName(),
            'type'              => trim((string) $request->input('type')) ?: $box->type,
            'file_path'         => $path,
            'mime_type'         => $file->getClientMimeType(),
            'size_bytes'        => $file->getSize(),
            'status'            => 'pending',
        ]);

        if ($request->expectsJson()) {
            return response()->json($document, 201);
        }

        return redirect()
            ->route('employee.documents.index')
            ->with('success', 'Document uploaded. It is now waiting for HR review.');
    }

    /**
     * GET /employee/documents/{document}/file
     * Streams the file inline (replaces the data: URL preview). Owner only.
     */
    public function file(Request $request, EmployeeDocument $document): StreamedResponse
    {
        abort_unless($document->employee_id === $request->user()->employee->id, 403);
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);

        $stream = Storage::disk('local')->readStream($document->file_path);
        abort_unless(is_resource($stream), 404);

        return new StreamedResponse(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $document->mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($document->name).'"',
        ]);
    }
}
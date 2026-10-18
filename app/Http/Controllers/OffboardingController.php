<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OffboardingCase;
use App\Models\OffboardingDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OffboardingController extends Controller
{
    /**
     * Offboarding Dashboard — HR-approved cases + modal data.
     */
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

        $disk = Storage::disk(OffboardingCase::DOCUMENT_DISK);

        // Data para sa Alpine modal, keyed by case id
        $cards = $cases->getCollection()->mapWithKeys(fn (OffboardingCase $c) => [
            $c->id => [
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
    }

    /**
     * Final approval — only after HR approved every submitted document.
     */
    public function approve(Request $request, OffboardingCase $offboardingCase): RedirectResponse
    {
        $offboardingCase->load('documents');

        if (! $offboardingCase->can_final_approve) {
            return back()->withErrors([
                'approve' => 'Final approval is available only after HR approves every submitted document.',
            ]);
        }

        $offboardingCase->update([
            'admin_final_approved' => true,
            'admin_approved_at'    => now(),
            'admin_approved_by'    => $request->user()?->id,
            'status'               => 'Closed',
            'progress'             => 100,
        ]);

        return back()->with('success', 'Final approval completed.');
    }

    /**
     * Open a submitted document (private disk).
     */
    public function document(OffboardingCase $offboardingCase, OffboardingDocument $document)
    {
        abort_unless(
            $offboardingCase->sent_to_admin && $document->offboarding_case_id === $offboardingCase->id,
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
            'Content-Disposition' => 'inline; filename="' . addslashes($document->file_name) . '"',
        ]);
    }
}
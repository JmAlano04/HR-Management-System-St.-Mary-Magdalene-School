<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\TimeCorrection;
use App\Services\HrmsNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Employee side: submit a correction request and see its review status.
 * app/Http/Controllers/TimeCorrectionController.php
 */
class TimeCorrectionController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        $hasSubmittedCorrection = $request->session()->get('time_correction_submitted', false);

        $latest = $employee && $hasSubmittedCorrection
            ? TimeCorrection::where('employee_id', $employee->id)->latest('id')->first()
            : null;

        return view('timeCorrection', [
            'employee' => $employee,
            'latest' => $latest,
            'today' => now()->toDateString(),
        ]);
    }

    public function attendancePunch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'correction_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'correction_type' => ['required', Rule::in(['Time In', 'Time Out'])],
        ]);

        $employee = $request->user()->employee;
        abort_unless($employee, 403);

        $column = $data['correction_type'] === 'Time In' ? 'time_in' : 'time_out';
        $time = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $data['correction_date'])
            ->value($column);

        return response()->json([
            'incorrect_time' => $time ? substr((string) $time, 0, 5) : null,
        ]);
    }

    public function store(StoreTimeCorrectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $proof = $request->file('proof');
        $proofPath = null;

        if ($proof) {
            $filename = Str::uuid().'.'.$proof->guessExtension();
            $directory = public_path('timecorrection');

            File::ensureDirectoryExists($directory);

            try {
                $proof->move($directory, $filename);
            } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException) {
                throw ValidationException::withMessages([
                    'proof' => 'The proof image could not be saved. Please try again.',
                ]);
            }

            $proofPath = 'timecorrection/'.$filename;
        }

        $correction = TimeCorrection::create([
            'employee_id' => $request->user()->employee->id,
            'correction_date' => $data['correction_date'],
            'correction_type' => $data['correction_type'],
            'incorrect_time' => $data['incorrect_time'] ?? null,
            'correct_time' => $data['correct_time'],
            'note' => $data['note'] ?? 'No note provided.', // column is NOT NULL
            'proof_path' => $proofPath,
            'proof_original_name' => $proof ? mb_substr($proof->getClientOriginalName(), 0, 255) : null,
            'hr_status' => TimeCorrection::PENDING,
            'admin_status' => TimeCorrection::PENDING,
        ]);

        HrmsNotifier::timeCorrectionSubmitted($correction);
        $request->session()->put('time_correction_submitted', true);

        return redirect()
            ->route('time-corrections.employee.index')
            ->with('success', 'Correction request submitted for HR and Admin review.');
    }

    /** Serve the private proof image to its owner, HR, or Admin only. */
    public function proof(Request $request, TimeCorrection $timeCorrection): StreamedResponse
    {
        $user = $request->user();
        $isOwner = $user->employee?->id === $timeCorrection->employee_id;
        $isStaff = $user->hasAnyRole(['HR', 'Admin']);

        abort_unless($isOwner || $isStaff, 403);

        $proofPath = $timeCorrection->proof_path;
        abort_unless($proofPath, 404);

        if (Str::startsWith($proofPath, ['timecorrection/', 'timecorreection/'])) {
            $directory = Str::startsWith($proofPath, 'timecorrection/')
                ? 'timecorrection'
                : 'timecorreection';
            $filename = substr($proofPath, strlen($directory) + 1);
            abort_unless($filename !== '' && basename($filename) === $filename, 404);

            $filePath = public_path($directory.'/'.$filename);
            abort_unless(File::isFile($filePath), 404);

            $stream = fopen($filePath, 'rb');
            $contentType = File::mimeType($filePath) ?: 'application/octet-stream';
        } else {
            $disk = Storage::disk('local');
            abort_unless($disk->exists($proofPath), 404);

            $stream = $disk->readStream($proofPath);
            $storagePath = $disk->path($proofPath);
            $contentType = File::mimeType($storagePath) ?: 'application/octet-stream';
        }

        abort_unless(is_resource($stream), 404);

        $originalFilename = $timeCorrection->proof_original_name ?: basename($proofPath);
        $fallbackFilename = Str::ascii($originalFilename) ?: 'proof';

        return new StreamedResponse(
            function () use ($stream): void {
                try {
                    fpassthru($stream);
                } finally {
                    fclose($stream);
                }
            },
            200,
            [
                'Content-Type' => $contentType,
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_INLINE,
                    $originalFilename,
                    $fallbackFilename
                ),
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}

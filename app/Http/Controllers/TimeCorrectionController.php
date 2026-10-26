<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeCorrectionRequest;
use App\Models\TimeCorrection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Employee side: submit a correction request and see its review status.
 * app/Http/Controllers/TimeCorrectionController.php
 */
class TimeCorrectionController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        $latest = $employee
            ? TimeCorrection::where('employee_id', $employee->id)->latest('id')->first()
            : null;

        return view('timeCorrection', [
            'employee' => $employee,
            'latest'   => $latest,
            'today'    => now()->toDateString(),
        ]);
    }

    public function store(StoreTimeCorrectionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        TimeCorrection::create([
            'employee_id'     => $request->user()->employee->id,
            'correction_date' => $data['correction_date'],
            'correction_type' => $data['correction_type'],
            'incorrect_time'  => $data['incorrect_time'] ?? null,
            'correct_time'    => $data['correct_time'],
            'note'            => $data['note'] ?? 'No note provided.', // column is NOT NULL
            'proof_path'      => $request->file('proof')?->store('time-corrections', 'local'), // private disk
            'hr_status'       => TimeCorrection::PENDING,
            'admin_status'    => TimeCorrection::PENDING,
        ]);

        return redirect()
            ->route('time-corrections.employee.index')
            ->with('success', 'Correction request submitted for HR and Admin review.');
    }

    /** Serve the private proof image to its owner, HR, or Admin only. */
    public function proof(Request $request, TimeCorrection $timeCorrection): BinaryFileResponse
    {
        $user    = $request->user();
        $isOwner = $user->employee?->id === $timeCorrection->employee_id;
        $isStaff = $user->hasAnyRole(['HR', 'Admin']);

        abort_unless($isOwner || $isStaff, 403);
        abort_unless(
            $timeCorrection->proof_path && Storage::disk('local')->exists($timeCorrection->proof_path),
            404
        );

        return response()->file(Storage::disk('local')->path($timeCorrection->proof_path));
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\TimeCorrection;
use App\Services\ApplyTimeCorrection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * HR / Admin side: review correction requests and record a decision.
 * app/Http/Controllers/TimeCorrectionReviewController.php
 */
class TimeCorrectionReviewController extends Controller
{
    /** Which status column belongs to the logged-in reviewer (Spatie roles). */
    private function reviewer(Request $request): array
    {
        $user = $request->user();

        if ($user->hasRole('HR')) {
            return ['prefix' => 'hr', 'label' => 'HR'];
        }

        if ($user->hasRole('Admin')) {
            return ['prefix' => 'admin', 'label' => 'Admin'];
        }

        abort(403);
    }

    public function index(Request $request): View
    {
        $reviewer = $this->reviewer($request);
        $column   = $reviewer['prefix'] . '_status';
        $view     = $request->query('view') === 'archive' ? 'archive' : 'current';

        $query = TimeCorrection::with('employee')->latest('id');

        if ($view === 'archive') {
            // Archive = both HR and Admin have decided
            $query->where('hr_status', '!=', TimeCorrection::PENDING)
                  ->where('admin_status', '!=', TimeCorrection::PENDING);
        } else {
            // Current = at least one of them still has to decide
            $query->where(fn ($q) => $q->where('hr_status', TimeCorrection::PENDING)
                                       ->orWhere('admin_status', TimeCorrection::PENDING));
        }

        $requests = $query->paginate(10)->withQueryString();

        // Data for the Alpine details modal, keyed by request id
        $cards = [];
        foreach ($requests as $r) {
            $cards[$r->id] = [
                'employee_name'  => $r->employee?->name ?? 'Unknown employee',
                'date_label'     => $r->correction_date?->format('F j, Y') ?? '—',
                'type'           => $r->correction_type,
                'incorrect_time' => $r->incorrect_time ? substr($r->incorrect_time, 0, 5) : '—',
                'correct_time'   => $r->correct_time ? substr($r->correct_time, 0, 5) : '—',
                'note'           => $r->note,
                'proof_url'      => $r->proof_path ? route('proof', $r) : null,
                'can_decide'     => $r->{$column} === TimeCorrection::PENDING,
            ];
        }

        // FIX: this must be the review view, not 'timeCorrection' (employee view)
        return view('timeCorrection', [
            'requests'     => $requests,
            'cards'        => $cards,
            'view'         => $view,
            'reviewer'     => $reviewer['label'],
            'pendingCount' => TimeCorrection::where($column, TimeCorrection::PENDING)->count(),
        ]);
    }

    public function decide(Request $request, ApplyTimeCorrection $applier): RedirectResponse
    {
        $reviewer = $this->reviewer($request);
        $prefix   = $reviewer['prefix'];
        $column   = $prefix . '_status';

        $data = $request->validate([
            'time_correction_id' => ['required', 'exists:time_corrections,id'],
            'decision'           => ['required', 'in:' . TimeCorrection::APPROVED . ',' . TimeCorrection::REJECTED],
        ]);

        $alreadyReviewed = false;
        $applied         = false;

        DB::transaction(function () use ($data, $column, $prefix, $request, $applier, &$alreadyReviewed, &$applied) {
            // Lock the row so HR and Admin approving at the same moment can't double-apply
            $correction = TimeCorrection::lockForUpdate()->findOrFail($data['time_correction_id']);

            if ($correction->{$column} !== TimeCorrection::PENDING) {
                $alreadyReviewed = true;
                return;
            }

            $correction->update([
                $column                 => $data['decision'],
                "{$prefix}_reviewed_at" => now(),
                "{$prefix}_reviewed_by" => $request->user()->id,
            ]);

            // Both HR and Admin approved -> write the corrected time to attendance
            if ($correction->isFullyApproved() && ! $correction->applied_at) {
                $applier->handle($correction);
                $applied = true;
            }
        });

        if ($alreadyReviewed) {
            return back()->withErrors(['decision' => 'You have already reviewed this request.']);
        }

        return back()->with('success', $applied
            ? 'Request Approved. Attendance has been updated.'
            : "Request {$data['decision']}.");
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\TimeCorrection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TimeCorrectionController extends Controller
{
    /** Admin reviews the "admin" column, HR reviews the "hr" column. */
    private function reviewerField(): string
    {
        $user = request()->user();

        if ($user->hasRole('Admin')) {
            return 'admin';
        }

        // Employees share the /time-corrections URL in the route group,
        // but they must never see or decide on the review queue.
        abort_unless($user->hasRole('HR'), 403, 'Only Admin and HR can review time corrections.');

        return 'hr';
    }

    public function index(Request $request)
    {
        $field = $this->reviewerField();
        $view  = $request->query('view') === 'archive' ? 'archive' : 'current';
        $col   = $field . '_status';

        $requests = TimeCorrection::with('employee')
            ->when($view === 'archive',
                fn ($q) => $q->where($col, '!=', TimeCorrection::PENDING),
                fn ($q) => $q->where($col, TimeCorrection::PENDING))
            ->orderByDesc('correction_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('timeCorrection', [
            'requests'     => $requests,
            'cards'        => $requests->getCollection()->map(fn ($r) => $r->toCard($field))->keyBy('id'),
            'view'         => $view,
            'reviewer'     => $field === 'admin' ? 'Admin' : 'HR',
            'pendingCount' => TimeCorrection::where($col, TimeCorrection::PENDING)->count(),
        ]);
    }

    /** Approve or reject (only the reviewer's own status is changed). */
    public function decide(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'time_correction_id' => ['required', 'integer', 'exists:time_corrections,id'],
            'decision'           => ['required', Rule::in([TimeCorrection::APPROVED, TimeCorrection::REJECTED])],
        ]);

        $timeCorrection = TimeCorrection::findOrFail($data['time_correction_id']);

        $field = $this->reviewerField();

        if ($timeCorrection->{$field . '_status'} !== TimeCorrection::PENDING) {
            return back()->withErrors(['decision' => 'This request has already been reviewed.']);
        }

        $timeCorrection->update([
            $field . '_status'      => $request->decision,
            $field . '_reviewed_at' => now(),
            $field . '_reviewed_by' => $request->user()->getAuthIdentifier(),
        ]);

        return redirect()
            ->route('time-corrections.index', ['view' => 'current'])
            ->with('success', 'Request ' . strtolower($request->decision) . '.');
    }

    /** Serves the private image proof (only Admin and HR can open it). */
    public function proof(TimeCorrection $timeCorrection)
    {
        abort_unless(
            $timeCorrection->proof_path && Storage::disk('local')->exists($timeCorrection->proof_path),
            404
        );

        return response()->file(Storage::disk('local')->path($timeCorrection->proof_path));
    }
}
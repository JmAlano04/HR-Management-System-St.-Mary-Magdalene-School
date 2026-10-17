<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveManagementController extends Controller
{
    /**
     * Leave Management — counts + Current/Archive list + details modal data.
     */
    public function index(Request $request): View
    {
        $view = $request->query('view', 'current') === 'archive' ? 'archive' : 'current';

        // Overview counts (non-archived only)
        $counts = [
            'pending'  => LeaveRequest::active()->where('status', 'pending')->count(),
            'approved' => LeaveRequest::active()->where('status', 'approved')->count(),
            'rejected' => LeaveRequest::active()->where('status', 'rejected')->count(),
            'total'    => LeaveRequest::active()->count(),
        ];

        $requests = LeaveRequest::query()
            ->when($view === 'archive',
                fn ($q) => $q->archived(),
                fn ($q) => $q->active())
            ->orderByDesc('requested_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        // Data for the Alpine modal, keyed by request id
        $cards = $requests->getCollection()->mapWithKeys(fn (LeaveRequest $r) => [
            $r->id => [
                'id'             => $r->id,
                'employee_name'  => $r->employee_name,
                'employee_code'  => $r->employee_code ?? 'Not provided',
                'department'     => $r->department ?? 'Not provided',
                'type'           => $r->type,
                'requested_date' => $r->requested_date->format('M d, Y'),
                'duration'       => $r->date_range,
                'days'           => $r->days_count . ' ' . str('day')->plural($r->days_count),
                'reason'         => $r->reason ?? 'Not provided',
                'leave_balance'  => $r->leave_balance ?? 'Not provided',
                'status'         => ucfirst($r->status),
                'can_decide'     => $r->status === 'pending' && ! $r->admin_archived,
                'status_url'     => route('leave.status', $r),
            ],
        ]);

        return view('leave', compact('requests', 'cards', 'counts', 'view'));
    }

    /**
     * Approve / Reject (pending requests only).
     */
    public function updateStatus(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
        ]);

        if ($leaveRequest->status !== 'pending' || $leaveRequest->admin_archived) {
            return back()->withErrors(['decision' => 'Only pending requests can be approved or rejected.']);
        }

        $leaveRequest->update([
            'status'     => $data['decision'],
            'decided_by' => $request->user()?->id,
            'decided_at' => now(),
        ]);

        return back()->with('success', "Leave request {$data['decision']}.");
    }

    /**
     * Move a request to the Archive.
     */
    public function archive(LeaveRequest $leaveRequest): RedirectResponse
    {
        $leaveRequest->update(['admin_archived' => true]);

        return back()->with('success', 'Request archived.');
    }

    /**
     * Permanently delete everything in the Archive.
     */
    public function clearArchive(): RedirectResponse
    {
        LeaveRequest::archived()->delete();

        return redirect()
            ->route('leaves.index', ['view' => 'archive'])
            ->with('success', 'Archive cleared.');
    }
}
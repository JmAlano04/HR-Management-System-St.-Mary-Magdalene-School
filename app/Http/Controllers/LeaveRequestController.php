<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Services\LeaveBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Employee side: balances, own requests, request leave, archive. */
class LeaveRequestController extends Controller
{
    public function __construct(private LeaveBalanceService $balances)
    {
    }

    public function index(Request $request): View
    {
        $user     = $request->user();
        $employee = $user->employee;

        $requests = LeaveRequest::forUser($user->id)->latest('id')->get();
        $balance  = $employee ? $this->balances->balances($employee) : null;

        return view('leave', [
            'employee'  => $employee,
            'balance'   => $balance,
            'remaining' => $balance ? $this->balances->remainingMap($balance) : [],
            'requests'  => $requests->map(fn (LeaveRequest $r) => [
                'id'          => $r->id,
                'label'       => $r->type,
                'requested'   => $r->requested_date?->format('M j, Y') ?? $r->created_at->format('M j, Y'),
                'range'       => $r->date_range . ' (' . $r->days_count . ' day' . ($r->days_count === 1 ? '' : 's') . ')',
                'reason'      => $r->reason,
                'status'      => $r->status_label,
                'archived'    => (bool) $r->employee_archived,
                'archive_url' => route('leave-requests.archive', $r),
            ])->values(),
        ]);
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $data     = $request->validated();
        $user     = $request->user();
        $employee = $user->employee;

        // Remaining days at the moment of filing (saved in leave_balance)
        $balances  = $this->balances->balances($employee, \Carbon\Carbon::parse($data['start_date'])->year);
        $remaining = $this->balances->remainingFor($balances, $data['leave_type']);

        LeaveRequest::create([
            'user_id'        => $user->id,
            'employee_code'  => $employee->employee_code,   // adjust to your employees column
            'employee_name'  => $user->name,
            'department'     => $employee->department,      // adjust to your employees column
            'type'           => $data['leave_type'] === 'Other'
                                    ? 'Others: ' . trim($data['other_leave_type'])
                                    : $data['leave_type'],
            'requested_date' => now()->toDateString(),
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'],
            'reason'         => $data['reason'],
            'leave_balance'  => (string) $remaining,
            'status'         => LeaveRequest::PENDING,
        ]);

        return redirect()
            ->route('leave-requests.index')
            ->with('success', 'Leave request submitted for review.');
    }

    public function archive(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->id === $leaveRequest->user_id, 403);

        $leaveRequest->update(['employee_archived' => true]);

        return redirect()->route('leave-requests.index')->with('success', 'Leave request archived.');
    }
}
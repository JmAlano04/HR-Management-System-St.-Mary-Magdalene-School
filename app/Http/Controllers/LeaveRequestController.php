<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
<<<<<<< HEAD
use App\Services\HrmsNotifier;
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use App\Services\LeaveBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

        // Non-regular: the service already returns only Non-Convertible Leave (5 days)
        $isNonRegular = $this->isNonRegular($employee);

<<<<<<< HEAD
=======
        // View file: resources/views/leave-requests/employee.blade.php
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        return view('leave', [
            'employee'     => $employee,
            'balance'      => $balance,
            'remaining'    => $balance ? $this->balances->remainingMap($balance) : [],
<<<<<<< HEAD
            // Use this in the leave type dropdown. null = regular employee (show every type).
            'isNonRegular' => $isNonRegular,
            'allowedTypes' => $isNonRegular ? [$this->nonRegularType()] : null,
            'requests'     => $requests->map(fn (LeaveRequest $r) => [
                'id'          => $r->id,
                'label'       => $r->type,
                'requested'   => $r->requested_date?->format('M j, Y') ?? $r->created_at->format('M j, Y'),
                'range'       => $r->date_range . ' (' . $r->days_count . ' day' . ($r->days_count === 1 ? '' : 's') . ')',
                'reason'      => $r->reason,
                'status'      => $r->status_label,
                'hr_status'   => ucfirst($r->hr_status ?? LeaveRequest::PENDING),
                'admin_status'=> ucfirst($r->admin_status ?? LeaveRequest::PENDING),
                'archived'    => (bool) $r->employee_archived,
                'archive_url' => route('leave-requests.archive', $r),
=======
            // null = regular employee (show every type)
            'isNonRegular' => $isNonRegular,
            'allowedTypes' => $isNonRegular ? [$this->nonRegularType()] : null,
            'requests'     => $requests->map(fn (LeaveRequest $r) => [
                'id'           => $r->id,
                'label'        => $r->type,
                'requested'    => $r->requested_date?->format('M j, Y') ?? $r->created_at->format('M j, Y'),
                'range'        => $r->date_range . ' (' . $r->days_count . ' day' . ($r->days_count === 1 ? '' : 's') . ')',
                'reason'       => $r->reason,
                'status'       => $r->status_label,
                // The Blade list reads these two. Adjust the column names if yours differ.
                'hr_status'    => ucfirst((string) ($r->hr_status ?? 'pending')),
                'admin_status' => ucfirst((string) ($r->admin_status ?? 'pending')),
                'archived'     => (bool) $r->employee_archived,
                'archive_url'  => route('leave-requests.archive', $r),
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            ])->values(),
        ]);
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $data     = $request->validated();
        $user     = $request->user();
        $employee = $user->employee;

<<<<<<< HEAD
=======
        // No linked employee record: the form is disabled, but guard the server too
        if (! $employee) {
            throw ValidationException::withMessages([
                'leave_type' => 'Your account is not linked to an employee record. Please contact HR.',
            ]);
        }

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $isNonRegular = $this->isNonRegular($employee);
        $nonRegType   = $this->nonRegularType();

        // Non-regular employees can only file Non-Convertible Leave (server-side check,
        // so it still holds if someone edits the form in the browser)
        if ($isNonRegular && $data['leave_type'] !== $nonRegType) {
            throw ValidationException::withMessages([
                'leave_type' => "Non-regular employees can only file {$nonRegType}.",
            ]);
        }

        // Remaining days at the moment of filing (saved in leave_balance)
        $balances  = $this->balances->balances($employee, \Carbon\Carbon::parse($data['start_date'])->year);
        $remaining = $this->balances->remainingFor($balances, $data['leave_type']);

        // Non-regular: cannot file more days than what is left of the 5 days
        if ($isNonRegular) {
            // forceFill so start_date / end_date are set even if they are not in $fillable
            $days = (new LeaveRequest)->forceFill([
                'start_date' => $data['start_date'],
                'end_date'   => $data['end_date'],
            ])->days_count;

            if ($remaining === null || $days > $remaining) {
                throw ValidationException::withMessages([
                    'end_date' => 'You only have ' . ($remaining ?? 0) . " day(s) of {$nonRegType} left.",
                ]);
            }
        }

<<<<<<< HEAD
        $leaveRequest = LeaveRequest::create([
=======
        LeaveRequest::create([
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
            'hr_status'      => LeaveRequest::PENDING,
            'admin_status'   => LeaveRequest::PENDING,
        ]);

        HrmsNotifier::leaveSubmitted($leaveRequest);

        return redirect()
            ->route('leaves.employee.index')
            ->with('success', 'Leave request submitted for review.');
=======
        ]);

        // back() avoids a "Route not defined" error if the index route name differs
        return back()->with('success', 'Leave request submitted for review.');
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    public function archive(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->id === $leaveRequest->user_id, 403);

        // forceFill so it works even if employee_archived is not in $fillable
        $leaveRequest->forceFill(['employee_archived' => true])->save();

<<<<<<< HEAD
        return redirect()->route('leaves.employee.index')->with('success', 'Leave request archived.');
=======
        return back()->with('success', 'Leave request archived.');
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    /** True when the employee is non-regular (same rule the balance service uses). */
    private function isNonRegular(?Employee $employee): bool
    {
        return $employee && $this->balances->status($employee) === 'non-regular';
    }

    /** The only leave type a non-regular employee can file. */
    private function nonRegularType(): string
    {
        return (string) config('leave.non_regular.type', 'Non-Convertible Leave');
    }
}
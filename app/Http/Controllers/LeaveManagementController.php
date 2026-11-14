<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Services\HrmsNotifier;
use App\Services\LeaveBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LeaveManagementController extends Controller
{
    /**
     * Leave Management — counts + Current/Archive list + details modal data.
     */
    public function index(Request $request): View
    {
        $view = $request->query('view', 'current') === 'archive' ? 'archive' : 'current';
        $reviewer = $request->user()->hasRole('HR') ? 'hr' : 'admin';

        // Overview counts (non-archived only)
        $counts = [
            'pending' => LeaveRequest::active()->where('status', 'pending')->count(),
            'approved' => LeaveRequest::active()->where('status', 'approved')->count(),
            'rejected' => LeaveRequest::active()->where('status', 'rejected')->count(),
            'total' => LeaveRequest::active()->count(),
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
                'id' => $r->id,
                'employee_name' => $r->employee_name,
                'employee_code' => $r->employee_code ?? 'Not provided',
                'department' => $r->department ?? 'Not provided',
                'type' => $r->type,
                'requested_date' => $r->requested_date->format('M d, Y'),
                'duration' => $r->date_range,
                'days' => $r->days_count.' '.str('day')->plural($r->days_count),
                'reason' => $r->reason ?? 'Not provided',
                'leave_balance' => $r->leave_balance ?? 'Not provided',
                'status' => $r->status_label,
                'hr_status' => ucfirst($r->hr_status ?? LeaveRequest::PENDING),
                'admin_status' => ucfirst($r->admin_status ?? LeaveRequest::PENDING),
                'rejection_reason' => $r->rejection_reason,
                'can_decide' => $r->status === LeaveRequest::PENDING
                    && ! $r->admin_archived
                    && ($reviewer === 'hr'
                        ? $r->hr_status === LeaveRequest::PENDING
                        : $r->hr_status === LeaveRequest::APPROVED
                            && $r->admin_status === LeaveRequest::PENDING),
                'status_url' => route('leave.status', $r),
            ],
        ]);

        return view('leave', compact('requests', 'cards', 'counts', 'view'));
    }

    public function balances(Request $request, LeaveBalanceService $service): JsonResponse
    {
        abort_unless($request->user()?->hasRole('HR'), 403);

        $year = now()->year;
        $employees = Employee::query()
            ->whereNotNull('user_id')
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($service, $year): array {
                $balances = $service->balances($employee, $year);

                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'employeeId' => $employee->employee_id,
                    'department' => Employee::DEPARTMENTS[$employee->department]
                        ?? Str::headline((string) $employee->department),
                    'status' => match ($balances['status']) {
                        'regular' => 'Regular',
                        'non-regular' => 'Probationary',
                        default => 'Unknown',
                    },
                    'total' => $balances['total'],
                    'types' => collect($balances['types'])
                        ->map(fn (array $balance, string $type): array => [
                            'name' => $type,
                            ...$balance,
                        ])
                        ->values(),
                ];
            })
            ->values();

        return response()
            ->json(['year' => $year, 'employees' => $employees])
            ->header('Cache-Control', 'no-store, private');
    }

    public function resetBalances(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasRole('HR'), 403);

        $now = now();
        DB::table('leave_balance_resets')->insert([
            'year' => $now->year,
            'reset_at' => $now,
            'reset_by' => $request->user()->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()
            ->json(['message' => 'All leave balances have been reset.', 'year' => $now->year])
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Approve / Reject (pending requests only).
     */
    public function updateStatus(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $reviewer = $user->hasRole('HR') ? 'hr' : ($user->hasRole('Admin') ? 'admin' : null);
        abort_if($reviewer === null, 403);
        $statusColumn = "{$reviewer}_status";
        $decidedByColumn = "{$reviewer}_decided_by";
        $decidedAtColumn = "{$reviewer}_decided_at";

        $result = DB::transaction(function () use (
            $leaveRequest,
            $data,
            $user,
            $reviewer,
            $statusColumn,
            $decidedByColumn,
            $decidedAtColumn,
        ): array {
            $lockedRequest = LeaveRequest::query()->lockForUpdate()->findOrFail($leaveRequest->id);

            if ($lockedRequest->status !== LeaveRequest::PENDING || $lockedRequest->admin_archived) {
                return ['error' => 'Only requests awaiting review can be approved or rejected.'];
            }

            if ($lockedRequest->{$statusColumn} !== LeaveRequest::PENDING) {
                return ['error' => 'You have already reviewed this leave request.'];
            }

            if ($reviewer === 'admin' && $lockedRequest->hr_status !== LeaveRequest::APPROVED) {
                return ['error' => 'Admin can review this request only after HR approves it.'];
            }

            $employee = null;
            if (
                $reviewer === 'admin'
                && $data['decision'] === LeaveRequest::APPROVED
                && $lockedRequest->hr_status === LeaveRequest::APPROVED
            ) {
                $employee = Employee::query()->where('user_id', $lockedRequest->user_id)->first();
                if (! $employee) {
                    return ['error' => 'The employee account is not linked to an employee record, so attendance could not be updated.'];
                }
            }

            $lockedRequest->{$statusColumn} = $data['decision'];
            $lockedRequest->{$decidedByColumn} = $user->id;
            $lockedRequest->{$decidedAtColumn} = now();

            if ($data['decision'] === LeaveRequest::REJECTED) {
                $lockedRequest->rejection_reason = $data['rejection_reason'];
                $lockedRequest->status = LeaveRequest::REJECTED;
                if ($reviewer === 'hr') {
                    $lockedRequest->admin_status = LeaveRequest::REJECTED;
                    $lockedRequest->admin_decided_at = now();
                }
            } elseif (
                $lockedRequest->hr_status === LeaveRequest::APPROVED
                && $lockedRequest->admin_status === LeaveRequest::APPROVED
            ) {
                $lockedRequest->status = LeaveRequest::APPROVED;
            }

            if ($lockedRequest->status !== LeaveRequest::PENDING) {
                $lockedRequest->decided_by = $user->id;
                $lockedRequest->decided_at = now();
            }

            $lockedRequest->save();

            if ($lockedRequest->status === LeaveRequest::APPROVED) {
                $this->markApprovedLeaveAttendance($lockedRequest, $employee);
            }

            return [
                'final' => $lockedRequest->status !== LeaveRequest::PENDING,
                'status' => $lockedRequest->status,
                'id' => $lockedRequest->id,
            ];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['decision' => $result['error']]);
        }

        if ($reviewer === 'hr') {
            HrmsNotifier::leaveHrReviewed(
                LeaveRequest::findOrFail($result['id']),
                $data['decision'],
                $data['rejection_reason'] ?? null,
            );
        }

        if ($result['final']) {
            HrmsNotifier::leaveDecided(
                LeaveRequest::findOrFail($result['id']),
                $data['rejection_reason'] ?? null,
            );

            return back()->with('success', "Leave request {$result['status']}.");
        }

        return back()->with('success', 'Your approval was recorded. The request is awaiting the other reviewer.');
    }

    private function markApprovedLeaveAttendance(LeaveRequest $leaveRequest, Employee $employee): void
    {
        $status = $this->approvedLeaveAttendanceStatus($leaveRequest);

        for ($date = $leaveRequest->start_date->copy(); $date->lte($leaveRequest->end_date); $date->addDay()) {
            Attendance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $date->toDateString(),
                ],
                [
                    'time_in' => null,
                    'time_out' => null,
                    'hours_worked' => 0,
                    'status' => $status,
                    'archived' => false,
                ]
            );
        }
    }

    private function approvedLeaveAttendanceStatus(LeaveRequest $leaveRequest): string
    {
        $nonConvertibleType = (string) config(
            'leave.non_regular.type',
            LeaveBalanceService::NON_REGULAR_TYPE
        );

        return strcasecmp(trim((string) $leaveRequest->type), trim($nonConvertibleType)) === 0
            ? Attendance::UNPAID_LEAVE
            : Attendance::PAID_LEAVE;
    }

    /**
     * Move a request to the Archive.
     */
    public function archive(LeaveRequest $leaveRequest): RedirectResponse
    {
        if (! in_array($leaveRequest->status, [LeaveRequest::APPROVED, LeaveRequest::REJECTED], true)) {
            return back()->withErrors(['decision' => 'Only completed leave requests can be archived.']);
        }

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

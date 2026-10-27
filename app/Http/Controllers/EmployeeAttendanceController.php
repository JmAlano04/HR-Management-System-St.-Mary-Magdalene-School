<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\SchoolEmployee;
use App\Support\AttendanceSchedule;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EmployeeAttendanceController extends Controller
{
    /**
     * Display the logged-in employee's attendance for a selected month.
     */
    public function index(Request $request)
    {
        // Ensure the user is authenticated.
        $user = $request->user();

        abort_unless($user, 401, 'Please log in to continue.');

        // Validate month and status filters.
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::in(AttendanceRecord::STATUSES)],
        ]);

        // Get the selected month.
        $month = Carbon::createFromFormat(
            '!Y-m',
            $filters['month'] ?? now()->format('Y-m')
        )->startOfMonth();

        // Find the employee linked to the logged-in user.
        $employee = SchoolEmployee::query()
            ->where('user_id', $user->id)
            ->first();

        // Default data passed to the Blade view.
        $data = [
            'employee' => $employee,
            'month' => $month,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'isCurrent' => $month->isSameMonth(now()),
            'status' => $filters['status'] ?? '',
            'schedule' => null,
            'records' => collect(),
            'summary' => [
                'present' => 0,
                'late' => 0,
                'half_day' => 0,
                'absent' => 0,
                'hours' => 0.0,
            ],
        ];

        // Show the page even if no employee record is linked.
        if (! $employee) {
            return view('attendance', $data);
        }

        // Schedule for the selected month (auto-adjusted in May).
        // NOTE: change `position` if your role column has a different name.
        $roleColumn = $employee->position;

        $data['schedule'] = AttendanceSchedule::for($roleColumn, $month);

        // Retrieve attendance records for the selected month.
        $base = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->where('archived', false)
            ->whereBetween('date', [
                $month->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ]);

        // Count attendance statuses for the month.
        $counts = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Monthly summary.
        $data['summary'] = [
            'present' => (int) ($counts['Present'] ?? 0),
            'late' => (int) ($counts['Late'] ?? 0),
            'half_day' => (int) ($counts['Half Day'] ?? 0),
            'absent' => (int) ($counts['Absent'] ?? 0),
            'hours' => (float) (clone $base)->sum('hours_worked'),
        ];

        // Apply the optional status filter, paginate, and attach computed values.
        $data['records'] = (clone $base)
            ->when(
                ! empty($filters['status']),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($record) use ($roleColumn) {
                $date = Carbon::parse($record->date)->startOfDay();
                $schedule = AttendanceSchedule::for($roleColumn, $date);

                $in = AttendanceSchedule::toCarbon($date, $record->time_in);
                $out = $record->time_out
                    ? Carbon::parse($date->toDateString().' '.$record->time_out)
                    : null;

                $record->late_minutes = $schedule->lateMinutes($in);
                $record->computed_hours = $schedule->hoursWorked($in, $out);
                $record->computed_status = $schedule->status($in, $out);

                return $record;
            });

        return view('attendance', $data);
    }
}
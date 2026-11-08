<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\SchoolEmployee;
use App\Support\AttendanceSchedule;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeAttendanceController extends Controller
{
    /**
<<<<<<< HEAD
     * Display the logged-in employee's attendance for a selected month.
     * Rows are shaped like the HR attendance table (see AttendanceController::present()).
     */
    public function index(Request $request)
    {
        // Ensure the user is authenticated.
=======
     * Show ONLY the logged-in user's own attendance for a selected month.
     *
     * Ownership chain:
     *   users.id -> employees.user_id -> attendance_records.employee_id (= employees.id)
     */
    public function index(Request $request)
    {
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $user = $request->user();

        abort_unless($user, 401, 'Please log in to continue.');

        // Validate month and status filters.
        $filters = $request->validate([
            'month'  => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::in(AttendanceRecord::STATUSES)],
        ]);

<<<<<<< HEAD
        // Get the selected month.
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $month = Carbon::createFromFormat(
            '!Y-m',
            $filters['month'] ?? now()->format('Y-m')
        )->startOfMonth();

<<<<<<< HEAD
        // Find the employee linked to the logged-in user.
=======
        // The employee that belongs to the logged-in account (never from the request).
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $employee = SchoolEmployee::query()
            ->where('user_id', $user->id)
            ->first();

        // Default data passed to the Blade view.
        $data = [
            'employee'  => $employee,
            'month'     => $month,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'isCurrent' => $month->isSameMonth(now()),
            'status'    => $filters['status'] ?? '',
            'schedule'  => null,
            'records'   => collect(),
            'summary'   => [
<<<<<<< HEAD
                'present'      => 0,
                'late'         => 0,
                'late_minutes' => 0,
                'half_day'     => 0,
                'absent'       => 0,
                'hours'        => 0.0,
            ],
        ];

        // Show the page even if no employee record is linked.
        if (! $employee) {
            return view('employees', $data);
        }

        // Schedule for the selected month (auto-adjusted in May).
        $data['schedule'] = AttendanceSchedule::forEmployee($employee->role_type, $employee->position, $month);

        // Retrieve attendance records for the selected month.
=======
                'present'  => 0,
                'late'     => 0,
                'half_day' => 0,
                'absent'   => 0,
                'hours'    => 0.0,
            ],
        ];

        // Account has no employee record -> show the page with the "not linked" message.
        if (! $employee) {
            return view('employee.attendance', $data);
        }

        $roleColumn = $employee->position;

        $data['schedule'] = AttendanceSchedule::for($roleColumn, $month);

        // Only this employee's, non-archived records inside the selected month.
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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

<<<<<<< HEAD
        // Monthly summary.
        $data['summary'] = [
            'present'      => (int) ($counts['Present'] ?? 0),
            'late'         => (int) ($counts['Late'] ?? 0),
            'late_minutes' => (int) (clone $base)->get(['date', 'time_in'])->sum(
                function ($record) use ($employee) {
                    $date = Carbon::parse($record->date)->startOfDay();
                    $schedule = AttendanceSchedule::forEmployee($employee->role_type, $employee->position, $date);
                    $timeIn = AttendanceSchedule::toCarbon($date, $record->time_in);

                    return $schedule->lateMinutes($timeIn);
                }
            ),
            'half_day'     => (int) ($counts['Half Day'] ?? 0),
            'absent'       => (int) ($counts['Absent'] ?? 0),
            'hours'        => (float) (clone $base)->get(['date', 'time_in', 'time_out', 'hours_worked'])
                ->sum(function ($record) use ($employee) {
                    $date = Carbon::parse($record->date)->startOfDay();
                    $schedule = AttendanceSchedule::forEmployee($employee->role_type, $employee->position, $date);
                    $timeIn = AttendanceSchedule::toCarbon($date, $record->time_in);
                    $timeOut = AttendanceSchedule::toCarbon($date, $record->time_out);
                    $computedHours = $schedule->hoursWorked($timeIn, $timeOut);

                    return $timeIn && $timeOut
                        ? $computedHours
                        : (float) $record->hours_worked;
                }),
        ];

        // Same for every row (the logged-in employee), like the HR table columns.
=======
        $data['summary'] = [
            'present'  => (int) ($counts['Present'] ?? 0),
            'late'     => (int) ($counts['Late'] ?? 0),
            'half_day' => (int) ($counts['Half Day'] ?? 0),
            'absent'   => (int) ($counts['Absent'] ?? 0),
            'hours'    => (float) (clone $base)->sum('hours_worked'),
        ];

        // Same for every row (the logged-in employee).
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $employeeCode    = $employee->employee_id;
        $employeeName    = $employee->name;
        $roleType        = SchoolEmployee::ROLE_TYPES[$employee->role_type]
            ?? Str::headline((string) $employee->role_type);
        $departmentLabel = SchoolEmployee::DEPARTMENTS[$employee->department]
            ?? Str::headline((string) $employee->department);

<<<<<<< HEAD
        // Apply the optional status filter, paginate, and shape each row for the table.
=======
        // Optional status filter, paginate, and shape each row for the table.
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $data['records'] = (clone $base)
            ->when(
                ! empty($filters['status']),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString()
<<<<<<< HEAD
            ->through(function ($record) use ($employee, $employeeCode, $employeeName, $roleType, $departmentLabel) {
                $date     = Carbon::parse($record->date)->startOfDay();
                $schedule = AttendanceSchedule::forEmployee($employee->role_type, $employee->position, $date);
=======
            ->through(function ($record) use ($roleColumn, $employeeCode, $employeeName, $roleType, $departmentLabel) {
                $date     = Carbon::parse($record->date)->startOfDay();
                $schedule = AttendanceSchedule::for($roleColumn, $date);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

                $in  = AttendanceSchedule::toCarbon($date, $record->time_in);
                $out = $record->time_out
                    ? Carbon::parse($date->toDateString().' '.$record->time_out)
                    : null;

<<<<<<< HEAD
                // Use scheduled hours when both punches exist; otherwise keep any stored partial total.
                $computed = (float) $schedule->hoursWorked($in, $out);
                $hours    = $in && $out ? $computed : (float) $record->hours_worked;
=======
                // Hours from the schedule (no overtime, lunch deducted);
                // falls back to the stored hours when there is no time out yet.
                $computed = (float) $schedule->hoursWorked($in, $out);
                $hours    = $computed > 0 ? $computed : (float) $record->hours_worked;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

                return [
                    'id'               => $record->id,
                    'employee_code'    => $employeeCode,
                    'name'             => $employeeName,
                    'role_type'        => $roleType,
                    'department_label' => $departmentLabel,
                    'date'             => $date->format('Y-m-d'),
                    'time_in'          => $record->time_in ? substr($record->time_in, 0, 5) : '',
                    'time_out'         => $record->time_out ? substr($record->time_out, 0, 5) : '',
                    'hours'            => number_format($hours, 2),
                    'late_minutes'     => (int) $schedule->lateMinutes($in),
                    'status'           => $record->status,
                ];
            });

        return view('attendance', $data);
    }
}
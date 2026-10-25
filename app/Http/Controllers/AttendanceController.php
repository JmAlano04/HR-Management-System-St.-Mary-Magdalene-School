<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\NonWorkingDay;
use App\Models\SchoolEmployee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    private const PER_PAGE = 25;

    /**
     * Attendance page.
     * ?view=current|archive  &folder=YYYY-MM-DD (archive)  &search= &date= &department= &status=
     */
    public function index(Request $request)
    {
        $view       = $request->query('view') === 'archive' ? 'archive' : 'current';
        $folderDate = $view === 'archive' ? $request->query('folder') : null;

        // Shared filters (all run in the database).
        $filtered = fn () => Attendance::query()
            ->when($view === 'archive', fn ($q) => $q->archived(), fn ($q) => $q->current())
            ->search($request->query('search'))
            ->inDepartment($request->query('department'))
            ->withStatus($request->query('status'));

        $folders = collect();
        $records = null;
        $exportRows = [];

        if ($view === 'archive' && ! $folderDate) {
            // Archive home: one folder per date.
            $folders = $filtered()
                ->selectRaw('date, COUNT(*) as total')
                ->groupBy('date')
                ->orderByDesc('date')
                ->get();
        } else {
            $records = $filtered()
                ->with('employee')
                ->onDate($view === 'archive' ? $folderDate : $request->query('date'))
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString();

            $nonWorkingDays = NonWorkingDay::whereIn(
                'date',
                $records->getCollection()->map(fn ($a) => $a->date->format('Y-m-d'))->unique()
            )->get()->keyBy(fn ($d) => $d->date->format('Y-m-d'));

            $records->through(
                fn (Attendance $a) => $this->present($a, $nonWorkingDays->get($a->date->format('Y-m-d')))
            );

            $exportRows = $records->getCollection()->map(fn ($r) => [
                'Employee ID'   => $r['employee_code'],
                'Name'          => $r['name'],
                'Employee Type' => $r['role_label'],
                'Department'    => $r['department_label'],
                'Date'          => $r['date'],
                'Time In'       => $r['time_in'],
                'Time Out'      => $r['time_out'],
                'Days Worked'   => $r['days_worked'],
                'Hours Worked'  => $r['hours'],
                'Late Minutes'  => $r['late_minutes'],
                'Status'        => $r['status'],
            ])->values();
        }

        // Employee list for the "Add Attendance" modal, keyed by id.
        $employees = SchoolEmployee::orderBy('name')->get()->mapWithKeys(fn ($e) => [
            $e->id => [
                'id'         => $e->id,
                'code'       => $e->employee_id,
                'name'       => $e->name,
                'role'       => SchoolEmployee::ROLE_TYPES[$e->role_type] ?? ucfirst((string) $e->role_type),
                'department' => SchoolEmployee::DEPARTMENTS[$e->department] ?? ucfirst((string) $e->department),
            ],
        ]);

        return view('attendance', [
            'view'           => $view,
            'folderDate'     => $folderDate,
            'folders'        => $folders,
            'records'        => $records,
            'employees'      => $employees,
            'exportRows'     => $exportRows,
            'statuses'       => Attendance::FORM_STATUSES,
            'nonWorkingDays' => NonWorkingDay::orderBy('date')->get(),
        ]);
    }

    /** Add attendance (one employee, one date). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('attendance', [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date'        => ['required', 'date'],
            'status'      => ['required', Rule::in(Attendance::FORM_STATUSES)],
            'time_in'     => ['nullable', 'date_format:H:i'],
            'time_out'    => ['nullable', 'date_format:H:i', 'after:time_in'],
        ], [
            'employee_id.required' => 'Select an employee and attendance date.',
            'time_out.after'       => 'Time out must be later than time in.',
        ]);

        $timeIn  = $data['time_in'] ?? null;
        $timeOut = $data['time_out'] ?? null;
        $hasBothTimes  = $timeIn && $timeOut;
        $requiresTimes = ! in_array($data['status'], Attendance::NO_TIME_STATUSES, true);

        if ((bool) $timeIn !== (bool) $timeOut) {
            return $this->formError('Enter both time in and time out, or leave both blank.');
        }

        if ($requiresTimes && ! $hasBothTimes) {
            return $this->formError('Time in and time out are required for Present, Late, and Half Day attendance.');
        }

        // Checks current AND archived records.
        if (Attendance::where('employee_id', $data['employee_id'])->whereDate('date', $data['date'])->exists()) {
            return $this->formError('This employee already has an attendance record for that date.');
        }

        $nonWorkingDay = NonWorkingDay::whereDate('date', $data['date'])->first();

        Attendance::create([
            'employee_id'  => $data['employee_id'],
            'date'         => $data['date'],
            'time_in'      => $timeIn,
            'time_out'     => $timeOut,
            'hours_worked' => $hasBothTimes ? Attendance::computeHours($timeIn, $timeOut) : null,
            'status'       => ($nonWorkingDay && $hasBothTimes) ? Attendance::NWD_REVIEW : $data['status'],
            'archived'     => false,
        ]);

        return redirect()
            ->route('attendance.index', ['date' => $data['date']])
            ->with('success', 'Attendance saved.');
    }

    /** Archive selected records. Body: ids[] */
    public function archive(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:attendance_records,id'],
        ]);

        $count = Attendance::current()->whereIn('id', $data['ids'])->update(['archived' => true]);

        return back()->with('success', "{$count} record(s) archived.");
    }

    /** Permanently delete selected records (archive only). Body: ids[] */
    public function destroyMany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:attendance_records,id'],
        ]);

        $count = Attendance::archived()->whereIn('id', $data['ids'])->delete();

        // If the opened folder is now empty, go back to the folder list.
        $folder = $request->input('folder');
        if ($folder && ! Attendance::archived()->whereDate('date', $folder)->exists()) {
            return redirect()->route('attendance.index', ['view' => 'archive'])
                ->with('success', "{$count} record(s) deleted.");
        }

        return back()->with('success', "{$count} record(s) deleted.");
    }

    /* ---------------- Helpers ---------------- */

    /** Shape one record for the Blade table. */
    private function present(Attendance $a, ?NonWorkingDay $nwd): array
    {
        $employee = $a->employee;
        $status   = $a->status;
        $hasLogs  = $a->time_in || $a->time_out;

        $daysWorked  = 0.0;
        $lateMinutes = 0;

        if (! $nwd) {
            $daysWorked  = Attendance::computeDaysWorked($status);
            $lateMinutes = $status === Attendance::LATE ? Attendance::computeLateMinutes($a->time_in) : 0;
        }

        // No logs on a non-working day => show the holiday instead of Absent.
        if ($nwd && ! $hasLogs && $status === Attendance::ABSENT) {
            $status = $nwd->isPaid() ? 'Paid Holiday' : 'Non-Working Day';
        }

        return [
            'id'               => $a->id,
            'employee_code'    => $employee?->employee_id,
            'name'             => $employee?->name,
            'initials'         => $employee?->initials,
            'role_label'       => SchoolEmployee::ROLE_TYPES[$employee?->role_type] ?? ucfirst((string) $employee?->role_type),
            'department_label' => SchoolEmployee::DEPARTMENTS[$employee?->department] ?? ucfirst((string) $employee?->department),
            'date'             => $a->date?->format('Y-m-d'),
            'time_in'          => $a->time_in ? substr($a->time_in, 0, 5) : '',
            'time_out'         => $a->time_out ? substr($a->time_out, 0, 5) : '',
            'hours'            => number_format((float) $a->hours_worked, 2),
            'days_worked'      => $daysWorked,
            'late_minutes'     => $lateMinutes,
            'status'           => $status,
            'review_note'      => ($nwd && $a->status === Attendance::NWD_REVIEW)
                ? "{$nwd->category}: {$nwd->name} - raw logs excluded from payroll"
                : null,
        ];
    }

    private function formError(string $message): RedirectResponse
    {
        return back()->withErrors(['form' => $message], 'attendance')->withInput();
    }
}
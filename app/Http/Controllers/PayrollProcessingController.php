<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Models\Attendance;
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
<<<<<<< HEAD
use App\Services\CashAdvancePayrollService;
use App\Support\AttendanceSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
=======
use App\Support\AttendanceSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollProcessingController extends Controller
{
<<<<<<< HEAD
    private const MONTHLY_DAYS       = 26;        // monthly wage = daily wage x 26
    private const LATE_PER_MINUTE    = 1.80;
    private const MALASAKITAN        = 50.00;     // every 15th and 30th pay period
    private const HONORARIUM         = 1924.31;   // administrative role only

    /** Contribution rates, deducted on the 30th pay period only. */
    private const RATES = [
        'philhealth' => 0.025,
        'peraa_ceap' => 0.04,
    ];
    private const HDMF_CONTRIBUTION = 200.00;
=======
    /** Working days of the week (ISO: 1 = Monday ... 7 = Sunday). Change to [1,2,3,4,5,6] for a 6-day week. */
    private const WORK_WEEKDAYS      = [1, 2, 3, 4, 5];
    private const MONTHLY_DAYS       = 26;        // monthly wage = daily wage x 26
    private const LATE_PER_MINUTE    = 1.80;
    private const MALASAKITAN        = 50.00;     // every 15th and 30th pay period
    private const HONORARIUM         = 1924.31;   // administrative role only (fixed)
    private const CASH_ADVANCE_CAP   = 2000.00;   // max deduction per pay period
    private const HDMF               = 200.00;    // fixed amount, every pay period
    private const MAX_HOURS_PER_DAY  = 8;         // no overtime in payroll

    /** Attendance statuses that count as a day worked. "Absent" does not. */
    private const WORKED_STATUSES = ['Present', 'Late', 'Half Day'];

    /** Contribution rates, deducted on the 30th pay period only. Base = monthly wage (daily wage x 26). */
    private const RATES = [
        'sss'        => 0.05,
        'philhealth' => 0.025,
        'peraa_ceap' => 0.04,
    ];

    /** Pay period => attendance cutoff day. */
    private const CUTOFF_DAY = ['15th' => '10th', '30th' => '25th'];
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

    /** Deduction columns of payroll_records that Accounting still enters manually. */
    private const DED = ['sss_loan', 'philhealth_loan', 'peraa_ceap_loan', 'hdmf_loan', 'cash_advance'];

    /** Columns that are auto-computed; stored as 0 in payroll_records. */
    private const AUTO = ['sss', 'philhealth', 'peraa_ceap', 'hdmf'];

    private const DEFAULT_WAGES = [
        550.00, 577.50, 606.38, 636.70, 668.53, 701.96, 737.06, 773.91, 812.60, 853.23,
        895.89, 940.69, 987.72, 1037.11, 1088.96, 1142.13, 1199.24, 1259.20, 1322.16, 1388.27,
        1457.68, 1530.56, 1607.09, 1687.45, 1771.82, 1860.41, 1953.43, 2051.10, 2153.66, 2261.34,
    ];

    /** Accounting only: payroll calculation for one month + pay period. */
    public function index(Request $request)
    {
        $this->authorizeAccounting();

        [$period, $cutoff] = $this->periodAndCutoff($request);
<<<<<<< HEAD
        $inputs = $this->inputs($period, $cutoff);
        $this->persistPayrollRecords($inputs, $period, $cutoff);

        $employees = $inputs->map(fn ($x) => [
            'id'             => $x['employee']->id,
            'name'           => $x['employee']->name,
            'employeeNumber' => $x['employee']->employee_id,
            'department'     => Employee::DEPARTMENTS[$x['employee']->department] ?? $x['employee']->department,
            'position'       => $x['employee']->position,
            'isAdmin'        => $this->isAdministrative($x['employee']),
            'step'           => (int) $x['in']['step_no'],
            'daysWorked'     => (float) $x['in']['days_worked'],
            'hoursWorked'    => (float) $x['in']['hours_worked'],
            'lateMinutes'    => (float) $x['in']['late_minutes'],
            'unpaidLeaveDays' => (float) $x['in']['unpaid_leave_days'],
            'honorarium'     => $this->isAdministrative($x['employee']) ? self::HONORARIUM : 0.0,
            'deductions'     => [
                'sssLoan'        => (float) $x['in']['sss_loan'],
                'philhealthLoan' => (float) $x['in']['philhealth_loan'],
                'peraaCeapLoan'  => (float) $x['in']['peraa_ceap_loan'],
                'hdmfLoan'       => (float) $x['in']['hdmf_loan'],
                'cashAdvance'    => (float) $x['in']['cash_advance'],   // prior month's approved total
            ],
        ])->values();
=======

        $employees = $this->inputs($period, $cutoff)->map(function ($x) {
            $isAdmin = $this->isAdministrative($x['employee']);

            return [
                'id'             => $x['employee']->id,
                'name'           => $x['employee']->name,
                'employeeNumber' => $x['employee']->employee_id,
                'department'     => Employee::DEPARTMENTS[$x['employee']->department] ?? $x['employee']->department,
                'position'       => $x['employee']->position,
                'isAdmin'        => $isAdmin,
                'step'           => (int) $x['in']['step_no'],   // fixed, taken from the payslip record
                // scheduled days / days / hours / late come from attendance_records (read-only)
                'scheduledDays'  => (int) $x['in']['scheduled_days'],
                'daysWorked'     => (float) $x['in']['days_worked'],
                'hoursWorked'    => (float) $x['in']['hours_worked'],
                'lateMinutes'    => (float) $x['in']['late_minutes'],
                'honorarium'     => $isAdmin ? self::HONORARIUM : 0.0,
                'deductions'     => [
                    'sssLoan'        => (float) $x['in']['sss_loan'],
                    'philhealthLoan' => (float) $x['in']['philhealth_loan'],
                    'peraaCeapLoan'  => (float) $x['in']['peraa_ceap_loan'],
                    'hdmfLoan'       => (float) $x['in']['hdmf_loan'],
                    'cashAdvance'    => (float) $x['in']['cash_advance'],   // outstanding balance
                ],
            ];
        })->values();
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        return view('payroll-processing', [
            'employees' => $employees,
            'steps'     => $this->steps(),
            'period'    => $period,
<<<<<<< HEAD
            'cutoff'    => $cutoff,
        ]);
    }

    /** Autosave of the table (called with fetch). Writes payroll_records only. */
=======
            'cutoff'    => $cutoff,                    // pay period key: 15th | 30th
            'cutoffs'   => $this->allCutoffs($period), // cutoff day + date range per pay period
        ]);
    }

    /**
     * Autosave of the table (called with fetch). Writes payroll_records only.
     * Days / hours / late are NOT taken from the browser: they are read from attendance_records.
     */
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    public function save(Request $request): JsonResponse
    {
        $this->authorizeAccounting();

        [$period, $cutoff] = $this->periodAndCutoff($request);

<<<<<<< HEAD
        $data = $request->validate([
            'rows'                    => ['required', 'array', 'min:1'],
            'rows.*.employee_id'      => ['required', 'integer', 'exists:employees,id'],
            'rows.*.step_no'          => ['required', 'integer', 'min:1', 'max:200'],
            'rows.*.days_worked'      => ['required', 'numeric', 'min:0', 'max:31'],
            'rows.*.hours_worked'     => ['required', 'numeric', 'min:0', 'max:744'],
            'rows.*.late_minutes'     => ['required', 'numeric', 'min:0', 'max:99999'],
=======
        // step_no and days/hours/late are intentionally not trusted from the request.
        $data = $request->validate([
            'rows'                    => ['required', 'array', 'min:1'],
            'rows.*.employee_id'      => ['required', 'integer', 'exists:employees,id'],
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'rows.*.sss_loan'         => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.philhealth_loan'  => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.peraa_ceap_loan'  => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.hdmf_loan'        => ['nullable', 'numeric', 'min:0', 'max:99999999'],
<<<<<<< HEAD
        ]);

        $requestedEmployeeIds = collect($data['rows'])->pluck('employee_id')->map(fn ($id) => (int) $id);
        $employees = Employee::excludeInternalStaff()
            ->whereIn('id', $requestedEmployeeIds)
            ->get()
            ->keyBy('id');

        if ($requestedEmployeeIds->diff($employees->keys())->isNotEmpty()) {
            return response()->json([
                'message' => 'One or more selected employees are not eligible for payroll processing.',
            ], 422);
        }

        $attendance = $this->attendanceRecordsFor($employees, $period, $cutoff);
        $cashAdvancePayroll = app(CashAdvancePayrollService::class);
        $cashAdvances = $cashAdvancePayroll->loansForPeriod($period, $employees->keys())
            ->groupBy('employee_id');
        $now       = now();

        $rows = collect($data['rows'])->map(function ($r) use ($employees, $attendance, $cashAdvances, $cashAdvancePayroll, $period, $cutoff, $now) {
            $employee = $employees[$r['employee_id']];
            $metrics = $this->attendanceMetrics($employee, $attendance->get($employee->id, collect()));
            $row = [
                'employee_id'  => $r['employee_id'],
                'step_no'      => $r['step_no'],
                'days_worked'  => $metrics['days_worked'],
                'hours_worked' => $metrics['hours_worked'],
                'late_minutes' => $metrics['late_minutes'],
                // honorarium is fixed by role; the browser value is ignored
                'honorarium'   => $this->isAdministrative($employee) ? self::HONORARIUM : 0,
=======
            'rows.*.cash_advance'     => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $employees = Employee::with('user')
            ->whereIn('id', collect($data['rows'])->pluck('employee_id'))
            ->get()
            ->keyBy('id');
        $attendance = $this->attendanceTotals($period, $cutoff, $employees);
        $fixed      = $this->fixedStepNos($this->steps());
        $existing   = DB::table('payroll_records')
            ->where(['period' => $period, 'cutoff' => $cutoff])
            ->pluck('step_no', 'employee_id');
        $now        = now();

        $rows = collect($data['rows'])->map(function ($r) use ($employees, $attendance, $fixed, $existing, $period, $cutoff, $now) {
            $id  = (int) $r['employee_id'];
            $att = $attendance[$id];
            $row = [
                'employee_id'  => $id,
                // fixed step: payslip table first, then the saved record, then step 1
                'step_no'      => $fixed[$id] ?? $existing[$id] ?? 1,
                'days_worked'  => $att['days_worked'],
                'hours_worked' => $att['hours_worked'],
                'late_minutes' => $att['late_minutes'],
                // honorarium is fixed by role; the browser value is ignored
                'honorarium'   => $this->isAdministrative($employees[$id]) ? self::HONORARIUM : 0,
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                'period'       => $period,
                'cutoff'       => $cutoff,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
            foreach (self::DED as $k) {
<<<<<<< HEAD
                $row[$k] = $k === 'cash_advance'
                    ? $cashAdvancePayroll->total($cashAdvances->get($employee->id, collect()))
                    : ($r[$k] ?? 0);
=======
                $row[$k] = $r[$k] ?? 0;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            }
            foreach (self::AUTO as $k) {
                $row[$k] = 0;   // auto-computed on the fly, nothing to store
            }
            return $row;
        })->all();

<<<<<<< HEAD
        $steps = $this->steps();
        $transferDate = Carbon::createFromFormat('Y-m-d', $period . '-01')->toDateString();

        DB::transaction(function () use ($rows, $employees, $steps, $cutoff, $transferDate) {
            DB::table('payroll_records')->upsert(
                $rows,
                ['period', 'cutoff', 'employee_id'],
                array_merge(
                    ['step_no', 'days_worked', 'hours_worked', 'late_minutes', 'honorarium', 'updated_at'],
                    self::DED,
                    self::AUTO
                )
            );

            foreach ($rows as $row) {
                $employee = $employees[$row['employee_id']];
                $payslip = Payslip::where([
                    'employee_id' => $row['employee_id'],
                    'cutoff' => $cutoff,
                    'transfer_date' => $transferDate,
                ])->first();

                if (! $payslip || $payslip->released_at) {
                    continue;
                }

                $values = $this->payslipValues($row, $steps, $cutoff, $employee);
                $changed = collect($values)->contains(function ($value, $key) use ($payslip) {
                    $current = $payslip->getAttribute($key);

                    if (is_numeric($value) && is_numeric($current)) {
                        return round((float) $value, 2) !== round((float) $current, 2);
                    }

                    return $value !== $current;
                });

                if (! $changed) {
                    continue;
                }

                $payslip->update($values + [
                    'sent_to_admin_at'       => null,
                    'admin_status'           => Payslip::PENDING,
                    'admin_rejection_reason' => null,
                    'admin_reviewed_at'      => null,
                    'admin_reviewed_by'      => null,
                    'hr_status'              => Payslip::PENDING,
                    'hr_rejection_reason'    => null,
                    'hr_reviewed_at'         => null,
                    'hr_reviewed_by'         => null,
                ]);
            }
        });
=======
        DB::table('payroll_records')->upsert(
            $rows,
            ['period', 'cutoff', 'employee_id'],
            array_merge(
                ['days_worked', 'hours_worked', 'late_minutes', 'honorarium', 'updated_at'],
                self::DED,
                self::AUTO
            )
        );
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        return response()->json(['saved' => count($rows)]);
    }

    /** Save the daily wage table (payroll_steps). */
    public function saveSteps(Request $request): JsonResponse
    {
        $this->authorizeAccounting();

        $data = $request->validate([
            'steps'             => ['required', 'array', 'min:1', 'max:60'],
            'steps.*.name'      => ['nullable', 'string', 'max:60'],
            'steps.*.dailyWage' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        DB::transaction(function () use ($data) {
            DB::table('payroll_steps')->delete();
            $now = now();
            foreach (array_values($data['steps']) as $i => $s) {
                DB::table('payroll_steps')->insert([
                    'step_no'    => $i + 1,
                    'name'       => trim($s['name'] ?? '') ?: 'Step ' . ($i + 1),
                    'daily_wage' => round((float) $s['dailyWage'], 2),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        return response()->json(['steps' => $this->steps()]);
    }

    /**
<<<<<<< HEAD
     * Transfer the SELECTED employees to Salary Monitoring. Reads the SAVED payroll_records
     * and recalculates everything on the server; nothing the browser calculated is trusted.
=======
     * Transfer the SELECTED employees to Salary Monitoring. Reads attendance_records and the
     * SAVED payroll_records and recalculates everything on the server; nothing the browser
     * calculated is trusted.
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
     */
    public function transfer(Request $request): RedirectResponse
    {
        $this->authorizeAccounting();

        [$period, $cutoff] = $this->periodAndCutoff($request);

        $ids = collect($request->validate([
            'employee_ids'   => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ], [
            'employee_ids.required' => 'Select at least one employee to transfer.',
        ])['employee_ids'])->map(fn ($id) => (int) $id);

        $steps        = $this->steps();
        $transferDate = Carbon::createFromFormat('Y-m-d', $period . '-01')->toDateString();
        $sent = 0;
<<<<<<< HEAD

        $inputs = $this->inputs($period, $cutoff);
        $eligibleEmployeeIds = $inputs->pluck('employee.id');

        if ($ids->diff($eligibleEmployeeIds)->isNotEmpty()) {
            return back()->withErrors([
                'employee_ids' => 'One or more selected employees are not eligible for payroll processing.',
            ]);
        }

        $selected = $inputs
            ->filter(fn ($x) => $ids->contains($x['employee']->id));

        DB::transaction(function () use ($selected, $cutoff, $steps, $transferDate, &$sent) {
=======
        $skipped = 0;

        $selected = $this->inputs($period, $cutoff)
            ->filter(fn ($x) => $ids->contains($x['employee']->id));

        DB::transaction(function () use ($selected, $cutoff, $steps, $transferDate, &$sent, &$skipped) {
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            foreach ($selected as $x) {
                $existing = Payslip::where([
                    'employee_id' => $x['employee']->id, 'cutoff' => $cutoff, 'transfer_date' => $transferDate,
                ])->first();

<<<<<<< HEAD
                if ($existing) {
                    $existing->response()->delete();
                }

                Payslip::updateOrCreate(
                    ['employee_id' => $x['employee']->id, 'cutoff' => $cutoff, 'transfer_date' => $transferDate],
                    $this->payslipValues($x['in'], $steps, $cutoff, $x['employee']) + [
=======
                // never overwrite a payslip already released to the employee
                if ($existing?->released_at) {
                    $skipped++;
                    continue;
                }

                $p = $this->compute($x['in'], $steps, $cutoff, $this->isAdministrative($x['employee']));
                $b = $p['breakdown'];

                Payslip::updateOrCreate(
                    ['employee_id' => $x['employee']->id, 'cutoff' => $cutoff, 'transfer_date' => $transferDate],
                    [
                        'step_name'          => $p['step']['name'],
                        'days_worked'        => $p['days'],
                        'hours_worked'       => $p['hours'],
                        'late_minutes'       => (int) round($p['lateMinutes']),
                        'basic_pay'          => $p['basicPay'],
                        'daily_wage'         => $p['step']['dailyWage'],
                        'honorarium'         => $p['honorarium'],
                        'gross_pay'          => $p['grossPay'],
                        'malasakitan'        => $b['malasakitan'],
                        'late_deduction'     => $b['late'],
                        'absences_deduction' => $b['absences'],
                        'sss'                => $b['sss'],
                        'sss_loan'           => $b['sss_loan'],
                        'philhealth'         => $b['philhealth'],
                        'philhealth_loan'    => $b['philhealth_loan'],
                        'peraa_ceap'         => $b['peraa_ceap'],
                        'peraa_ceap_loan'    => $b['peraa_ceap_loan'],
                        'hdmf'               => $b['hdmf'],
                        'hdmf_loan'          => $b['hdmf_loan'],
                        'cash_advance'       => $b['cash_advance'],
                        'total_deductions'   => $p['totalDeductions'],
                        'net_pay'            => $p['netPay'],
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

                        // a fresh transfer restarts the approval chain
                        'sent_to_admin_at'       => null,
                        'admin_status'           => Payslip::PENDING,
                        'admin_rejection_reason' => null,
                        'admin_reviewed_at'      => null,
                        'admin_reviewed_by'      => null,
                        'hr_status'              => Payslip::PENDING,
                        'hr_rejection_reason'    => null,
                        'hr_reviewed_at'         => null,
                        'hr_reviewed_by'         => null,
                        'released_at'            => null,
                    ]
                );
                $sent++;
            }
        });

<<<<<<< HEAD
        $msg = "Transferred {$sent} payslip" . ($sent === 1 ? '' : 's') . " ({$cutoff} pay period, {$period}) to Salary Monitoring.";
=======
        $info = $this->cutoffInfo($period, $cutoff);
        $msg  = "Transferred {$sent} payslip" . ($sent === 1 ? '' : 's')
              . " ({$cutoff} pay period, {$info['day']} cutoff: {$info['range']}) to Salary Monitoring.";
        if ($skipped) {
            $msg .= " {$skipped} already released and were left unchanged.";
        }
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        return redirect()->route('salary-monitoring.index')->with('success', $msg);
    }

    // ------------------------------------------------------------------

<<<<<<< HEAD
    /** Every employee with their saved payroll_record, or defaults when none exists yet. */
=======
    /**
     * Everything attendance-related for the pay period, built from the database:
     *
     *  - scheduled_days: working days (Mon-Fri) inside the cutoff window, minus unpaid
     *    non-working days (non_working_days.pay_type = 'unpaid')
     *  - days_worked / hours_worked / late_minutes: from attendance_records (time_in / time_out)
     *      * a day counts when the status is Present, Late or Half Day ("Absent" does not)
     *      * hours come from the work schedule (lunch deducted, no overtime), falling back
     *        to the stored hours_worked when there is no time out; capped at 8 per day
     *      * late minutes = sum of the late minutes of each day (after the grace period)
     *  - paid non-working days (non_working_days.pay_type = 'paid') on a working day are
     *    credited (paid_days / paid_hours) when the employee has no attendance that day
     *
     * Absences are then scheduled_days - days_worked, so a day with no attendance counts as absent.
     *
     * @param  iterable<Employee>  $employees  only ->id and ->position are used
     * @return array<int, array{scheduled_days: int, days_worked: float, hours_worked: float, late_minutes: float}>
     */
    private function attendanceTotals(string $period, string $cutoff, iterable $employees): array
    {
        $byId = [];
        foreach ($employees as $e) {
            $byId[$e->id] = $e;
        }

        [$from, $to] = $this->cutoffWindow($period, $cutoff);

        // non-working days in the window (holidays etc.)
        $nonWorking = DB::table('non_working_days')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn ($n) => Carbon::parse($n->date)->toDateString());

        // working days in the window; unpaid non-working days are not scheduled at all
        $scheduled = 0;
        $paidHolidays = [];   // [Y-m-d => [paid_days, paid_hours]]
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if (! in_array($d->dayOfWeekIso, self::WORK_WEEKDAYS, true)) {
                continue;
            }
            $key = $d->toDateString();
            $nwd = $nonWorking[$key] ?? null;

            if ($nwd && strtolower((string) $nwd->pay_type) !== 'paid') {
                continue;   // unpaid holiday: no work expected, no pay, not an absence
            }
            $scheduled++;
            if ($nwd) {
                $paidHolidays[$key] = [(float) $nwd->paid_days, (float) $nwd->paid_hours];
            }
        }

        // every employee gets a row, even when they have no attendance in the window
        $totals = [];
        foreach ($byId as $id => $_) {
            $totals[$id] = [
                'scheduled_days' => $scheduled,
                'days_worked'    => 0.0,
                'hours_worked'   => 0.0,
                'late_minutes'   => 0.0,
            ];
        }
        if (! $byId) {
            return $totals;
        }

        $records = AttendanceRecord::query()
            ->whereIn('employee_id', array_keys($byId))
            ->where('archived', false)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', self::WORKED_STATUSES)
            ->get();

        $attended = [];   // [employee_id => [Y-m-d => true]]

        foreach ($records as $rec) {
            $date     = Carbon::parse($rec->date)->startOfDay();
            $schedule = AttendanceSchedule::for($byId[$rec->employee_id]->position, $date);

            $in  = $rec->time_in ? AttendanceSchedule::toCarbon($date, $rec->time_in) : null;
            $out = $rec->time_out ? Carbon::parse($date->toDateString() . ' ' . $rec->time_out) : null;

            $hours = $in ? (float) $schedule->hoursWorked($in, $out) : 0.0;
            if ($hours <= 0) {
                $hours = (float) $rec->hours_worked;
            }
            $hours = min(max($hours, 0), self::MAX_HOURS_PER_DAY);

            $totals[$rec->employee_id]['days_worked']  += 1;
            $totals[$rec->employee_id]['hours_worked'] += $hours;
            $totals[$rec->employee_id]['late_minutes'] += $in ? (int) $schedule->lateMinutes($in) : 0;

            $attended[$rec->employee_id][$date->toDateString()] = true;
        }

        // paid holidays: credit employees who have no attendance that day
        foreach ($paidHolidays as $dateKey => [$paidDays, $paidHours]) {
            foreach (array_keys($byId) as $id) {
                if (empty($attended[$id][$dateKey])) {
                    $totals[$id]['days_worked']  += $paidDays;
                    $totals[$id]['hours_worked'] += $paidHours;
                }
            }
        }

        foreach ($totals as &$t) {
            $t['hours_worked'] = round($t['hours_worked'], 2);
            $t['late_minutes'] = round($t['late_minutes'], 2);
        }
        unset($t);

        return $totals;
    }

    /**
     * Fixed step per employee, taken from their latest payslip (payslips.step_name).
     * Returns [employee_id => step_no]. Employees with no payslip yet are not included.
     */
    private function fixedStepNos(array $steps): array
    {
        $byName = [];
        foreach ($steps as $i => $s) {
            $byName[$s['name']] = $i + 1;
        }

        return Payslip::orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->get(['employee_id', 'step_name'])
            ->unique('employee_id')
            ->filter(fn ($p) => isset($byName[$p->step_name]))
            ->mapWithKeys(fn ($p) => [$p->employee_id => $byName[$p->step_name]])
            ->all();
    }

    /**
     * Every employee with their saved payroll_record (loans, cash advance, step) and the
     * attendance totals for the cutoff window (days, hours, late).
     */
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    private function inputs(string $period, string $cutoff)
    {
        $records = DB::table('payroll_records')
            ->where(['period' => $period, 'cutoff' => $cutoff])
            ->get()->keyBy('employee_id');

<<<<<<< HEAD
        $defaults = array_merge(
            ['step_no' => 1, 'days_worked' => 0, 'hours_worked' => 0, 'late_minutes' => 0, 'honorarium' => 0],
            array_fill_keys(array_merge(self::DED, self::AUTO), 0)
        );

        $employees = Employee::excludeInternalStaff()->orderBy('name')->get();
        $attendance = $this->attendanceRecordsFor($employees, $period, $cutoff);
        $cashAdvancePayroll = app(CashAdvancePayrollService::class);
        $cashAdvances = $cashAdvancePayroll->loansForPeriod($period, $employees->pluck('id'))
            ->groupBy('employee_id');

        return $employees->map(function (Employee $employee) use ($records, $defaults, $attendance, $cashAdvances, $cashAdvancePayroll) {
            $inputs = array_merge($defaults, (array) ($records[$employee->id] ?? []));
            $inputs['step_no'] = (int) $employee->step_no;
            $inputs['honorarium'] = $this->isAdministrative($employee) ? self::HONORARIUM : 0;
            $inputs['cash_advance'] = $cashAdvancePayroll->total(
                $cashAdvances->get($employee->id, collect())
            );
            $inputs = array_merge(
                $inputs,
                $this->attendanceMetrics($employee, $attendance->get($employee->id, collect()))
            );

            return [
                'employee' => $employee,
                'in'       => $inputs,
            ];
        });
    }

    private function persistPayrollRecords(Collection $employees, string $period, string $cutoff): void
    {
        $now = now();
        $rows = $employees->map(function (array $entry) use ($period, $cutoff, $now) {
            $inputs = $entry['in'];

            return [
                'employee_id' => $entry['employee']->id,
                'period' => $period,
                'cutoff' => $cutoff,
                'step_no' => $inputs['step_no'],
                'days_worked' => $inputs['days_worked'],
                'hours_worked' => $inputs['hours_worked'],
                'late_minutes' => $inputs['late_minutes'],
                'honorarium' => $inputs['honorarium'],
                ...array_intersect_key($inputs, array_flip(array_merge(self::DED, self::AUTO))),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        if ($rows === []) {
            return;
        }

        DB::table('payroll_records')->upsert(
            $rows,
            ['period', 'cutoff', 'employee_id'],
            array_merge(
                ['step_no', 'days_worked', 'hours_worked', 'late_minutes', 'honorarium', 'updated_at'],
                self::DED,
                self::AUTO
            )
        );
    }

    private function attendanceRecordsFor(Collection $employees, string $period, string $cutoff): Collection
    {
        if ($employees->isEmpty()) {
            return collect();
        }

        [$start, $end] = $this->cutoffDateRange($period, $cutoff);

        return AttendanceRecord::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('archived', false)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('employee_id');
    }

    private function cutoffDateRange(string $period, string $cutoff): array
    {
        $month = Carbon::createFromFormat('Y-m-d', $period.'-01');

        return $cutoff === '15th'
            ? [$month->copy()->subMonthNoOverflow()->day(21)->toDateString(), $month->copy()->day(5)->toDateString()]
            : [$month->copy()->day(6)->toDateString(), $month->copy()->day(20)->toDateString()];
    }

    private function attendanceMetrics(Employee $employee, iterable $records): array
    {
        $daysWorked = 0.0;
        $hoursWorked = 0.0;
        $lateMinutes = 0;
        $unpaidLeaveDays = 0.0;

        foreach ($records as $record) {
            $date = Carbon::parse($record->date)->startOfDay();
            $schedule = AttendanceSchedule::forEmployee($employee->role_type, $employee->position, $date);
            $timeIn = AttendanceSchedule::toCarbon($date, $record->time_in);
            $timeOut = AttendanceSchedule::toCarbon($date, $record->time_out);
            $computedHours = $schedule->hoursWorked($timeIn, $timeOut);

            if ($record->status === Attendance::NWD_REVIEW) {
                if ($timeIn && $timeOut) {
                    $hoursWorked += $computedHours;
                    $daysWorked += $computedHours / 8;
                }

                continue;
            }

            if ($record->status === Attendance::PAID_LEAVE) {
                $daysWorked += 1;
                $hoursWorked += 8;

                continue;
            }

            if ($record->status === Attendance::UNPAID_LEAVE) {
                // Unpaid leave contributes no paid days or hours, reducing basic pay.
                $unpaidLeaveDays += 1;

                continue;
            }

            $daysWorked += Attendance::computeDaysWorked($record->status);
            $hoursWorked += $timeIn && $timeOut
                ? $computedHours
                : (float) $record->hours_worked;
            $lateMinutes += $schedule->lateMinutes($timeIn);
        }

        return [
            'days_worked' => round($daysWorked, 2),
            'hours_worked' => round($hoursWorked, 2),
            'late_minutes' => $lateMinutes,
            'unpaid_leave_days' => round($unpaidLeaveDays, 2),
        ];
    }

    /**
     * Payroll rules:
     *  - monthly wage: daily wage x 26
     *  - late: 1.80 / minute
     *  - MALASAKITAN: 50 on every pay period
     *  - SSS 5%, HDMF 2%, PhilHealth 2.5%, PERAA/CEAP 4%: 30th pay period only (base = monthly wage)
     *  - loans (SSS, PhilHealth, PERAA/CEAP, HDMF): manually entered and deducted each pay period
     *  - prior month's cash advances: split equally between the 15th and 30th pay periods
     *  - honorarium: fixed 1,924.31, administrative role only
     */
    private function payslipValues(array $inputs, array $steps, string $cutoff, Employee $employee): array
    {
        $pay = $this->compute($inputs, $steps, $cutoff, $this->isAdministrative($employee));
        $deductions = $pay['breakdown'];

        return [
            'step_name'          => $pay['step']['name'],
            'days_worked'        => $pay['days'],
            'hours_worked'       => $pay['hours'],
            'late_minutes'       => (int) round($pay['lateMinutes']),
            'basic_pay'          => $pay['basicPay'],
            'daily_wage'         => $pay['step']['dailyWage'],
            'honorarium'         => $pay['honorarium'],
            'gross_pay'          => $pay['grossPay'],
            'malasakitan'        => $deductions['malasakitan'],
            'late_deduction'     => $deductions['late'],
            'absences_deduction' => 0.0,
            'sss'                => $deductions['sss'],
            'sss_loan'           => $deductions['sss_loan'],
            'philhealth'         => $deductions['philhealth'],
            'philhealth_loan'    => $deductions['philhealth_loan'],
            'peraa_ceap'         => $deductions['peraa_ceap'],
            'peraa_ceap_loan'    => $deductions['peraa_ceap_loan'],
            'hdmf'               => $deductions['hdmf'],
            'hdmf_loan'          => $deductions['hdmf_loan'],
            'cash_advance'       => $deductions['cash_advance'],
            'total_deductions'   => $pay['totalDeductions'],
            'net_pay'            => $pay['netPay'],
        ];
    }

=======
        $fixed = $this->fixedStepNos($this->steps());

        $defaults = array_merge(
            ['step_no' => 1, 'scheduled_days' => 0, 'days_worked' => 0, 'hours_worked' => 0, 'late_minutes' => 0, 'honorarium' => 0],
            array_fill_keys(array_merge(self::DED, self::AUTO), 0)
        );

        // with('user') loads the linked account once, so the role check does not query per employee.
        // Remove it if your Employee model has no user() relation.
        $employees  = Employee::with('user')->orderBy('name')->get();
        $attendance = $this->attendanceTotals($period, $cutoff, $employees);

        return $employees->map(function ($e) use ($records, $defaults, $fixed, $attendance) {
            $in = array_merge($defaults, (array) ($records[$e->id] ?? []));
            // attendance is the source of truth for days / hours / late
            $in = array_merge($in, $attendance[$e->id]);
            // the step is fixed: payslip table wins over anything saved in payroll_records
            $in['step_no'] = $fixed[$e->id] ?? $in['step_no'];

            return ['employee' => $e, 'in' => $in];
        });
    }

    /**
     * Payroll rules:
     *  - pay periods: 15th and 30th. Attendance cutoffs: 10th (15th pay period) and 25th (30th pay period)
     *  - scheduled days / days / hours / late: read from the database (attendance_records + non_working_days)
     *  - absences: (scheduled days - days worked) x daily wage
     *  - monthly wage: daily wage x 26
     *  - late: 1.80 / minute
     *  - MALASAKITAN: 50 on every pay period
     *  - SSS 5%, PhilHealth 2.5%, PERAA/CEAP 4%: 30th pay period only (base = monthly wage)
     *  - HDMF: fixed 200, every pay period
     *  - loans (SSS, PhilHealth, PERAA/CEAP, HDMF): manual input, deducted on the 30th pay period only
     *  - cash advance: max 2,000 per pay period, on both pay periods
     *  - honorarium: fixed 1,924.31, administrative role only
     */
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    private function compute(array $r, array $steps, string $cutoff, bool $isAdmin): array
    {
        $step  = $steps[((int) $r['step_no']) - 1] ?? ['name' => 'Unassigned', 'dailyWage' => 0];
        $wage  = (float) $step['dailyWage'];
        $days  = (float) $r['days_worked'];
        $hours = min((float) $r['hours_worked'], $days * 8);

<<<<<<< HEAD
        $absences = 0.0;

        $late      = (float) $r['late_minutes'];
        $lateMinutes = max(0, $late);
        $lateDed = $lateMinutes * self::LATE_PER_MINUTE;
=======
        $absences = max(0, (float) ($r['scheduled_days'] ?? 0) - $days) * $wage;

        $missing   = max(0, $days * 8 * 60 - $hours * 60);
        $late      = (float) $r['late_minutes'];
        $effective = max(0, min($late, $missing ?: $late));
        $lateDed   = $effective * self::LATE_PER_MINUTE;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        $ratio = $days > 0 ? min(max(($hours / $days) / 8, 0), 1) : 0;
        $basic = $wage * $days * $ratio;
        $honor = $isAdmin ? self::HONORARIUM : 0;
        $gross = $basic + $honor;

        $g      = fn (string $k) => max(0, (float) ($r[$k] ?? 0));
        $only30 = $cutoff === '30th';

        $monthlyWage = $wage * self::MONTHLY_DAYS;
        $contrib     = fn (string $k) => $only30 ? $monthlyWage * self::RATES[$k] : 0;
<<<<<<< HEAD
        $sss          = $only30 ? $this->sssContribution($monthlyWage) : 0;

        $cashAdvance = $g('cash_advance');
        $firstCashAdvanceInstallment = round($cashAdvance / 2, 2);

        $breakdown = [
            'malasakitan'     => self::MALASAKITAN,
            'late'            => $lateDed,
            'absences'        => 0.0,
            'sss'             => $sss,
            'philhealth'      => $contrib('philhealth'),
            'hdmf'            => $only30 ? self::HDMF_CONTRIBUTION : 0,
            'peraa_ceap'      => $contrib('peraa_ceap'),
            'sss_loan'        => $g('sss_loan'),
            'philhealth_loan' => $g('philhealth_loan'),
            'peraa_ceap_loan' => $g('peraa_ceap_loan'),
            'hdmf_loan'       => $g('hdmf_loan'),
            'cash_advance'    => $only30
                ? round($cashAdvance - $firstCashAdvanceInstallment, 2)
                : $firstCashAdvanceInstallment,
=======

        // TODO: loans above 30,000 are deducted on the 15th too (paid within 10 months,
        // one request per year). Needs the principal from loan_requests.
        $breakdown = [
            'malasakitan'     => self::MALASAKITAN,
            'late'            => $lateDed,
            'absences'        => $absences,
            'sss'             => $contrib('sss'),
            'philhealth'      => $contrib('philhealth'),
            'hdmf'            => self::HDMF,
            'peraa_ceap'      => $contrib('peraa_ceap'),
            'sss_loan'        => $only30 ? $g('sss_loan') : 0,
            'philhealth_loan' => $only30 ? $g('philhealth_loan') : 0,
            'peraa_ceap_loan' => $only30 ? $g('peraa_ceap_loan') : 0,
            'hdmf_loan'       => $only30 ? $g('hdmf_loan') : 0,
            // cash_advance holds the outstanding balance; deduct up to the cap each pay period
            'cash_advance'    => min(self::CASH_ADVANCE_CAP, $g('cash_advance')),
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        ];
        $breakdown = array_map(fn ($v) => round($v, 2), $breakdown);
        $total     = round(array_sum($breakdown), 2);

        return [
            'step'            => ['name' => $step['name'], 'dailyWage' => $wage],
            'monthlyWage'     => round($monthlyWage, 2),
            'days'            => $days,
            'hours'           => $hours,
<<<<<<< HEAD
            'lateMinutes'     => $lateMinutes,
=======
            'lateMinutes'     => $effective,
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'basicPay'        => round($basic, 2),
            'honorarium'      => round($honor, 2),
            'grossPay'        => round($gross, 2),
            'breakdown'       => $breakdown,
            'totalDeductions' => $total,
            'netPay'          => round($gross - $total, 2),
        ];
    }

<<<<<<< HEAD
    private function sssContribution(float $monthlyWage): float
    {
        if ($monthlyWage < 12750) {
            return 0;
        }

        return min(1000, 650 + floor(($monthlyWage - 12750) / 500) * 25);
    }

    /** Administrative task/role gets the fixed honorarium. Adjust to your employees table. */
    private function isAdministrative(Employee $e): bool
    {
        $roleType = strtolower(trim((string) $e->role_type));
        $position = strtolower(trim((string) $e->position));

        return $roleType === 'administrative staff'
            || str_contains($position, 'administrat')
            || strtolower(trim((string) $e->department)) === 'administrative';
=======
    /**
     * Administrative role gets the fixed honorarium of P1,924.31.
     * Checks every text field on the employee record (role, position, department,
     * employee type, etc.) and the roles on the linked user account.
     */
    private function isAdministrative(Employee $e): bool
    {
        $isAdmin = fn ($v) => is_string($v) && str_contains(strtolower($v), 'administrative');

        // 1. Any text column on the employee record
        foreach ($e->getAttributes() as $value) {
            if ($isAdmin($value)) {
                return true;
            }
        }
        if ($isAdmin(Employee::DEPARTMENTS[$e->role_type] ?? null)) {
            return true;
        }

        // 2. Role on the linked user account (Spatie)
        $user = method_exists($e, 'user') ? $e->user : null;

        return $user instanceof User
            && $user->getRoleNames()->contains(fn ($role) => $isAdmin($role));
    }

    /**
     * Attendance dates covered by a pay period of the given month (Y-m).
     *  - 15th pay period: 10th cutoff, covers the 26th of the previous month to the 10th
     *  - 30th pay period: 25th cutoff, covers the 11th to the 25th
     * Adjust the dates here if your actual cutoff windows differ.
     *
     * @return array{0: Carbon, 1: Carbon} [from, to]
     */
    private function cutoffWindow(string $period, string $payPeriod): array
    {
        $m = Carbon::createFromFormat('Y-m-d', $period . '-01')->startOfDay();

        if ($payPeriod === '15th') {
            return [$m->copy()->subMonthNoOverflow()->day(26), $m->copy()->day(10)];
        }

        return [$m->copy()->day(11), $m->copy()->day(25)];
    }

    /** Cutoff day and date range label for display. */
    private function cutoffInfo(string $period, string $payPeriod): array
    {
        [$from, $to] = $this->cutoffWindow($period, $payPeriod);

        return [
            'day'   => self::CUTOFF_DAY[$payPeriod],
            'range' => $from->format('M j') . ' - ' . $to->format('M j, Y'),
        ];
    }

    private function allCutoffs(string $period): array
    {
        return [
            '15th' => $this->cutoffInfo($period, '15th'),
            '30th' => $this->cutoffInfo($period, '30th'),
        ];
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    private function periodAndCutoff(Request $request): array
    {
        $v = validator($request->all(), [
            'period' => ['nullable', 'date_format:Y-m'],
<<<<<<< HEAD
            'cutoff' => ['nullable', 'in:15th,30th'],
=======
            'cutoff' => ['nullable', 'in:15th,30th'],   // pay period key
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        ])->validate();

        return [$v['period'] ?? now()->format('Y-m'), $v['cutoff'] ?? '15th'];
    }

    /** Steps from the DB, seeded with the default wage table the first time. */
    private function steps(): array
    {
        if (DB::table('payroll_steps')->count() === 0) {
            $now = now();
            foreach (self::DEFAULT_WAGES as $i => $w) {
                DB::table('payroll_steps')->insert([
                    'step_no' => $i + 1, 'name' => 'Step ' . ($i + 1), 'daily_wage' => $w,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        return DB::table('payroll_steps')->orderBy('step_no')->get()
            ->map(fn ($s) => ['name' => $s->name, 'dailyWage' => (float) $s->daily_wage])
            ->all();
    }

    private function authorizeAccounting(): void
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User && $user->hasRole('Accounting'),
            403,
            'Only Accounting can process payroll.'
        );
    }
}
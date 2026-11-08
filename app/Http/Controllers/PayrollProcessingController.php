<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use App\Services\CashAdvancePayrollService;
use App\Support\AttendanceSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollProcessingController extends Controller
{
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

        return view('payroll-processing', [
            'employees' => $employees,
            'steps'     => $this->steps(),
            'period'    => $period,
            'cutoff'    => $cutoff,
        ]);
    }

    /** Autosave of the table (called with fetch). Writes payroll_records only. */
    public function save(Request $request): JsonResponse
    {
        $this->authorizeAccounting();

        [$period, $cutoff] = $this->periodAndCutoff($request);

        $data = $request->validate([
            'rows'                    => ['required', 'array', 'min:1'],
            'rows.*.employee_id'      => ['required', 'integer', 'exists:employees,id'],
            'rows.*.step_no'          => ['required', 'integer', 'min:1', 'max:200'],
            'rows.*.days_worked'      => ['required', 'numeric', 'min:0', 'max:31'],
            'rows.*.hours_worked'     => ['required', 'numeric', 'min:0', 'max:744'],
            'rows.*.late_minutes'     => ['required', 'numeric', 'min:0', 'max:99999'],
            'rows.*.sss_loan'         => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.philhealth_loan'  => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.peraa_ceap_loan'  => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.hdmf_loan'        => ['nullable', 'numeric', 'min:0', 'max:99999999'],
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
                'period'       => $period,
                'cutoff'       => $cutoff,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
            foreach (self::DED as $k) {
                $row[$k] = $k === 'cash_advance'
                    ? $cashAdvancePayroll->total($cashAdvances->get($employee->id, collect()))
                    : ($r[$k] ?? 0);
            }
            foreach (self::AUTO as $k) {
                $row[$k] = 0;   // auto-computed on the fly, nothing to store
            }
            return $row;
        })->all();

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
     * Transfer the SELECTED employees to Salary Monitoring. Reads the SAVED payroll_records
     * and recalculates everything on the server; nothing the browser calculated is trusted.
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
            foreach ($selected as $x) {
                $existing = Payslip::where([
                    'employee_id' => $x['employee']->id, 'cutoff' => $cutoff, 'transfer_date' => $transferDate,
                ])->first();

                if ($existing) {
                    $existing->response()->delete();
                }

                Payslip::updateOrCreate(
                    ['employee_id' => $x['employee']->id, 'cutoff' => $cutoff, 'transfer_date' => $transferDate],
                    $this->payslipValues($x['in'], $steps, $cutoff, $x['employee']) + [

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

        $msg = "Transferred {$sent} payslip" . ($sent === 1 ? '' : 's') . " ({$cutoff} pay period, {$period}) to Salary Monitoring.";

        return redirect()->route('salary-monitoring.index')->with('success', $msg);
    }

    // ------------------------------------------------------------------

    /** Every employee with their saved payroll_record, or defaults when none exists yet. */
    private function inputs(string $period, string $cutoff)
    {
        $records = DB::table('payroll_records')
            ->where(['period' => $period, 'cutoff' => $cutoff])
            ->get()->keyBy('employee_id');

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

    private function compute(array $r, array $steps, string $cutoff, bool $isAdmin): array
    {
        $step  = $steps[((int) $r['step_no']) - 1] ?? ['name' => 'Unassigned', 'dailyWage' => 0];
        $wage  = (float) $step['dailyWage'];
        $days  = (float) $r['days_worked'];
        $hours = min((float) $r['hours_worked'], $days * 8);

        $absences = 0.0;

        $late      = (float) $r['late_minutes'];
        $lateMinutes = max(0, $late);
        $lateDed = $lateMinutes * self::LATE_PER_MINUTE;

        $ratio = $days > 0 ? min(max(($hours / $days) / 8, 0), 1) : 0;
        $basic = $wage * $days * $ratio;
        $honor = $isAdmin ? self::HONORARIUM : 0;
        $gross = $basic + $honor;

        $g      = fn (string $k) => max(0, (float) ($r[$k] ?? 0));
        $only30 = $cutoff === '30th';

        $monthlyWage = $wage * self::MONTHLY_DAYS;
        $contrib     = fn (string $k) => $only30 ? $monthlyWage * self::RATES[$k] : 0;
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
        ];
        $breakdown = array_map(fn ($v) => round($v, 2), $breakdown);
        $total     = round(array_sum($breakdown), 2);

        return [
            'step'            => ['name' => $step['name'], 'dailyWage' => $wage],
            'monthlyWage'     => round($monthlyWage, 2),
            'days'            => $days,
            'hours'           => $hours,
            'lateMinutes'     => $lateMinutes,
            'basicPay'        => round($basic, 2),
            'honorarium'      => round($honor, 2),
            'grossPay'        => round($gross, 2),
            'breakdown'       => $breakdown,
            'totalDeductions' => $total,
            'netPay'          => round($gross - $total, 2),
        ];
    }

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
    }

    private function periodAndCutoff(Request $request): array
    {
        $v = validator($request->all(), [
            'period' => ['nullable', 'date_format:Y-m'],
            'cutoff' => ['nullable', 'in:15th,30th'],
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
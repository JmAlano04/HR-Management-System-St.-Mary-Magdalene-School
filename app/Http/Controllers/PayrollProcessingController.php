<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollProcessingController extends Controller
{
    private const SCHEDULED_WORKDAYS = 22;
    private const LATE_PER_MINUTE    = 1.80;

    /** Editable deduction columns of payroll_records. */
    private const DED = ['sss', 'sss_loan', 'philhealth', 'peraa_ceap', 'peraa_ceap_loan', 'hdmf', 'hdmf_loan', 'cash_advance'];

    private const DEFAULT_WAGES = [
        550.00, 577.50, 606.38, 636.70, 668.53, 701.96, 737.06, 773.91, 812.60, 853.23,
        895.89, 940.69, 987.72, 1037.11, 1088.96, 1142.13, 1199.24, 1259.20, 1322.16, 1388.27,
        1457.68, 1530.56, 1607.09, 1687.45, 1771.82, 1860.41, 1953.43, 2051.10, 2153.66, 2261.34,
    ];

    /** Accounting only: payroll calculation for one month + cutoff. */
    public function index(Request $request)
    {
        $this->authorizeAccounting();

        [$period, $cutoff] = $this->periodAndCutoff($request);

        $employees = $this->inputs($period, $cutoff)->map(fn ($x) => [
            'id'             => $x['employee']->id,
            'name'           => $x['employee']->name,
            'employeeNumber' => $x['employee']->employee_id,
            'department'     => Employee::DEPARTMENTS[$x['employee']->department] ?? $x['employee']->department,
            'position'       => $x['employee']->position,
            'step'           => (int) $x['in']['step_no'],
            'daysWorked'     => (float) $x['in']['days_worked'],
            'hoursWorked'    => (float) $x['in']['hours_worked'],
            'lateMinutes'    => (float) $x['in']['late_minutes'],
            'honorarium'     => (float) $x['in']['honorarium'],
            'deductions'     => [
                'sss' => (float) $x['in']['sss'], 'sssLoan' => (float) $x['in']['sss_loan'],
                'philhealth' => (float) $x['in']['philhealth'],
                'peraaCeap' => (float) $x['in']['peraa_ceap'], 'peraaCeapLoan' => (float) $x['in']['peraa_ceap_loan'],
                'hdmf' => (float) $x['in']['hdmf'], 'hdmfLoan' => (float) $x['in']['hdmf_loan'],
                'cashAdvance' => (float) $x['in']['cash_advance'],
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
            'rows.*.honorarium'       => ['required', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.sss'              => ['required', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.sss_loan'         => ['required', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.philhealth'       => ['required', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.peraa_ceap'       => ['required', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.peraa_ceap_loan'  => ['required', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.hdmf'             => ['required', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.hdmf_loan'        => ['required', 'numeric', 'min:0', 'max:99999999'],
            'rows.*.cash_advance'     => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $now  = now();
        $rows = collect($data['rows'])->map(fn ($r) => array_merge(
            collect($r)->only(array_merge(
                ['employee_id', 'step_no', 'days_worked', 'hours_worked', 'late_minutes', 'honorarium'], self::DED
            ))->all(),
            ['period' => $period, 'cutoff' => $cutoff, 'created_at' => $now, 'updated_at' => $now]
        ))->all();

        DB::table('payroll_records')->upsert(
            $rows,
            ['period', 'cutoff', 'employee_id'],
            array_merge(['step_no', 'days_worked', 'hours_worked', 'late_minutes', 'honorarium', 'updated_at'], self::DED)
        );

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
     * Transfer to Salary Monitoring. Reads the SAVED payroll_records and recalculates
     * everything on the server; nothing the browser calculated is trusted.
     */
    public function transfer(Request $request): RedirectResponse
    {
        $this->authorizeAccounting();

        [$period, $cutoff] = $this->periodAndCutoff($request);

        $steps        = $this->steps();
        $transferDate = Carbon::createFromFormat('Y-m-d', $period . '-01')->toDateString();
        $sent = 0;
        $skipped = 0;

        DB::transaction(function () use ($period, $cutoff, $steps, $transferDate, &$sent, &$skipped) {
            foreach ($this->inputs($period, $cutoff) as $x) {
                $existing = Payslip::where([
                    'employee_id' => $x['employee']->id, 'cutoff' => $cutoff, 'transfer_date' => $transferDate,
                ])->first();

                // never overwrite a payslip already released to the employee
                if ($existing?->released_at) {
                    $skipped++;
                    continue;
                }

                $p = $this->compute($x['in'], $steps, $cutoff);
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
                        'peraa_ceap'         => $b['peraa_ceap'],
                        'peraa_ceap_loan'    => $b['peraa_ceap_loan'],
                        'hdmf'               => $b['hdmf'],
                        'hdmf_loan'          => $b['hdmf_loan'],
                        'cash_advance'       => $b['cash_advance'],
                        'total_deductions'   => $p['totalDeductions'],
                        'net_pay'            => $p['netPay'],

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

        $msg = "Transferred {$sent} payslip" . ($sent === 1 ? '' : 's') . " ({$cutoff} cutoff, {$period}) to Salary Monitoring.";
        if ($skipped) {
            $msg .= " {$skipped} already released and were left unchanged.";
        }

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
            ['step_no' => 1, 'days_worked' => self::SCHEDULED_WORKDAYS, 'hours_worked' => self::SCHEDULED_WORKDAYS * 8,
             'late_minutes' => 0, 'honorarium' => 0],
            array_fill_keys(self::DED, 0)
        );

        return Employee::orderBy('name')->get()->map(fn ($e) => [
            'employee' => $e,
            'in'       => array_merge($defaults, (array) ($records[$e->id] ?? [])),
        ]);
    }

    /** PHP port of getEmployeePayroll() from the old script. */
    private function compute(array $r, array $steps, string $cutoff): array
    {
        $step  = $steps[((int) $r['step_no']) - 1] ?? ['name' => 'Unassigned', 'dailyWage' => 0];
        $wage  = (float) $step['dailyWage'];
        $days  = (float) $r['days_worked'];
        $hours = min((float) $r['hours_worked'], $days * 8);

        $absences = max(0, self::SCHEDULED_WORKDAYS - $days) * $wage;

        $missing   = max(0, $days * 8 * 60 - $hours * 60);
        $late      = (float) $r['late_minutes'];
        $effective = max(0, min($late, $missing ?: $late));
        $lateDed   = $effective * self::LATE_PER_MINUTE;

        $ratio = $days > 0 ? min(max(($hours / $days) / 8, 0), 1) : 0;
        $basic = $wage * $days * $ratio;
        $honor = max(0, (float) $r['honorarium']);
        $gross = $basic + $honor;

        $g      = fn (string $k) => max(0, (float) ($r[$k] ?? 0));
        $only30 = $cutoff === '30th';

        $breakdown = [
            'malasakitan'     => 50.0,
            'late'            => $lateDed,
            'absences'        => $absences,
            'sss'             => $only30 ? $g('sss') : 0,
            'sss_loan'        => $only30 ? $g('sss_loan') : 0,
            'philhealth'      => $only30 ? $g('philhealth') : 0,
            'peraa_ceap'      => $only30 ? $g('peraa_ceap') : 0,
            'peraa_ceap_loan' => $only30 ? $g('peraa_ceap_loan') : 0,
            'hdmf'            => $only30 ? $g('hdmf') : 0,
            'hdmf_loan'       => $only30 ? $g('hdmf_loan') : 0,
            'cash_advance'    => $g('cash_advance') / 2,   // monthly amount, half per cutoff
        ];
        $breakdown = array_map(fn ($v) => round($v, 2), $breakdown);
        $total     = round(array_sum($breakdown), 2);

        return [
            'step'            => ['name' => $step['name'], 'dailyWage' => $wage],
            'days'            => $days,
            'hours'           => $hours,
            'lateMinutes'     => $effective,
            'basicPay'        => round($basic, 2),
            'honorarium'      => round($honor, 2),
            'grossPay'        => round($gross, 2),
            'breakdown'       => $breakdown,
            'totalDeductions' => $total,
            'netPay'          => round($gross - $total, 2),
        ];
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
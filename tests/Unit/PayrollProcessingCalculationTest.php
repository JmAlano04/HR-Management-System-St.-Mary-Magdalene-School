<?php

use App\Http\Controllers\PayrollProcessingController;
use App\Models\Employee;
use App\Models\LoanRequest;
use App\Services\CashAdvancePayrollService;
use Illuminate\Support\Collection;

function calculatePayrollProcessingRow(
    string $cutoff,
    float $lateMinutes = 0,
    float $hoursWorked = 176,
    float $cashAdvance = 0
): array
{
    $controller = new PayrollProcessingController;
    $calculate = new ReflectionMethod($controller, 'compute');

    return $calculate->invoke($controller, [
        'step_no' => 1,
        'days_worked' => 22,
        'hours_worked' => $hoursWorked,
        'late_minutes' => $lateMinutes,
        'sss_loan' => 0,
        'philhealth_loan' => 0,
        'peraa_ceap_loan' => 0,
        'hdmf_loan' => 0,
        'cash_advance' => $cashAdvance,
    ], [['name' => 'Step 1', 'dailyWage' => 650]], $cutoff, false);
}

test('HDMF contribution is fixed at 200 on the 30th pay period only', function () {
    $fifteenth = calculatePayrollProcessingRow('15th');
    $thirtieth = calculatePayrollProcessingRow('30th');

    expect($fifteenth['breakdown']['hdmf'])->toBe(0.0)
        ->and($thirtieth['breakdown']['hdmf'])->toBe(200.0);
});

test('administrative staff receive a 1924.31 peso honorarium in payroll', function () {
    $controller = new PayrollProcessingController;
    $isAdministrative = new ReflectionMethod($controller, 'isAdministrative');
    $compute = new ReflectionMethod($controller, 'compute');
    $employee = new Employee([
        'role_type' => 'Administrative Staff',
        'position' => 'Office Staff',
        'department' => 'highschool',
    ]);

    $pay = $compute->invoke($controller, [
        'step_no' => 1,
        'days_worked' => 0,
        'hours_worked' => 0,
        'late_minutes' => 0,
    ], [['name' => 'Step 1', 'dailyWage' => 650]], '15th', true);

    expect($isAdministrative->invoke($controller, $employee))->toBeTrue()
        ->and($pay['honorarium'])->toBe(1924.31)
        ->and($pay['grossPay'])->toBe(1924.31);
});

test('cash advances are deducted in equal installments on both payroll cutoffs', function () {
    $fifteenth = calculatePayrollProcessingRow('15th', cashAdvance: 2000);
    $thirtieth = calculatePayrollProcessingRow('30th', cashAdvance: 2000);

    expect($fifteenth['breakdown']['cash_advance'])->toBe(1000.0)
        ->and($thirtieth['breakdown']['cash_advance'])->toBe(1000.0);
});

test('cash advance installments reconcile to the monthly total when there are cents', function () {
    $fifteenth = calculatePayrollProcessingRow('15th', cashAdvance: 2000.01);
    $thirtieth = calculatePayrollProcessingRow('30th', cashAdvance: 2000.01);

    expect($fifteenth['breakdown']['cash_advance'])->toBe(1000.01)
        ->and($thirtieth['breakdown']['cash_advance'])->toBe(1000.0)
        ->and($fifteenth['breakdown']['cash_advance'] + $thirtieth['breakdown']['cash_advance'])
        ->toBe(2000.01);
});

test('cash advance repayment allocations split the total across cutoff records', function () {
    $service = new CashAdvancePayrollService;
    $firstLoan = new LoanRequest;
    $firstLoan->id = 10;
    $firstLoan->payroll_balance = 1500.0;
    $secondLoan = new LoanRequest;
    $secondLoan->id = 11;
    $secondLoan->payroll_balance = 500.0;
    $loans = new Collection([$firstLoan, $secondLoan]);

    $fifteenth = $service->installmentAllocations($loans, '15th');
    $thirtieth = $service->installmentAllocations($loans, '30th');

    expect(array_sum($fifteenth))->toBe(1000.0)
        ->and(array_sum($thirtieth))->toBe(1000.0);
});

test('payroll attendance metrics use attendance records and the employee schedule', function () {
    $controller = new PayrollProcessingController;
    $summarize = new ReflectionMethod($controller, 'attendanceMetrics');
    $employee = new Employee(['position' => 'teacher']);
    $records = new Collection([
        (object) [
            'date' => '2026-03-02',
            'time_in' => '07:11:00',
            'time_out' => '16:30:00',
            'status' => 'Late',
            'hours_worked' => null,
        ],
        (object) [
            'date' => '2026-03-03',
            'time_in' => '08:00:00',
            'time_out' => '12:00:00',
            'status' => 'Half Day',
            'hours_worked' => null,
        ],
    ]);

    expect($summarize->invoke($controller, $employee, $records))
        ->toBe(['days_worked' => 1.5, 'hours_worked' => 11.82, 'late_minutes' => 71, 'unpaid_leave_days' => 0.0]);
});

test('payroll credits a full day when an employee clocks in within grace and out at 4:30 PM', function () {
    $controller = new PayrollProcessingController;
    $summarize = new ReflectionMethod($controller, 'attendanceMetrics');
    $employee = new Employee(['position' => 'teacher']);
    $records = new Collection([
        (object) [
            'date' => '2026-03-02',
            'time_in' => '07:10:00',
            'time_out' => '16:30:00',
            'status' => 'Present',
            'hours_worked' => null,
        ],
    ]);

    expect($summarize->invoke($controller, $employee, $records))
        ->toBe(['days_worked' => 1.0, 'hours_worked' => 8.0, 'late_minutes' => 0, 'unpaid_leave_days' => 0.0]);
});

test('payroll does not credit time worked after the scheduled end', function () {
    $controller = new PayrollProcessingController;
    $summarize = new ReflectionMethod($controller, 'attendanceMetrics');
    $employee = new Employee(['position' => 'teacher']);
    $records = new Collection([
        (object) [
            'date' => '2026-03-02',
            'time_in' => '07:00:00',
            'time_out' => '18:00:00',
            'status' => 'Present',
            'hours_worked' => 11,
        ],
    ]);

    expect($summarize->invoke($controller, $employee, $records))
        ->toBe(['days_worked' => 1.0, 'hours_worked' => 8.0, 'late_minutes' => 0, 'unpaid_leave_days' => 0.0]);
});

test('payroll counts hours worked on a non-working day review record', function () {
    $controller = new PayrollProcessingController;
    $summarize = new ReflectionMethod($controller, 'attendanceMetrics');
    $employee = new Employee(['position' => 'teacher']);
    $records = new Collection([
        (object) [
            'date' => '2026-03-02',
            'time_in' => '07:00:00',
            'time_out' => '16:30:00',
            'status' => 'Non-Working Day Review',
            'hours_worked' => null,
        ],
    ]);

    expect($summarize->invoke($controller, $employee, $records))
        ->toBe(['days_worked' => 1.0, 'hours_worked' => 8.0, 'late_minutes' => 0, 'unpaid_leave_days' => 0.0]);
});

test('non-convertible leave is unpaid and reduces payroll credit without changing the payslip fields', function () {
    $controller = new PayrollProcessingController;
    $summarize = new ReflectionMethod($controller, 'attendanceMetrics');
    $compute = new ReflectionMethod($controller, 'compute');
    $employee = new Employee(['position' => 'teacher']);
    $records = new Collection([
        (object) [
            'date' => '2026-03-02',
            'time_in' => null,
            'time_out' => null,
            'status' => 'Paid Leave',
            'hours_worked' => 0,
        ],
        (object) [
            'date' => '2026-03-03',
            'time_in' => null,
            'time_out' => null,
            'status' => 'Unpaid Leave',
            'hours_worked' => 0,
        ],
    ]);

    $metrics = $summarize->invoke($controller, $employee, $records);
    $pay = $compute->invoke($controller, [
        'step_no' => 1,
        'days_worked' => $metrics['days_worked'],
        'hours_worked' => $metrics['hours_worked'],
        'late_minutes' => $metrics['late_minutes'],
    ], [['name' => 'Step 1', 'dailyWage' => 650]], '15th', false);
    $payWithTwoPaidDays = $compute->invoke($controller, [
        'step_no' => 1,
        'days_worked' => 2,
        'hours_worked' => 16,
        'late_minutes' => 0,
    ], [['name' => 'Step 1', 'dailyWage' => 650]], '15th', false);

    expect($metrics)
        ->toBe([
            'days_worked' => 1.0,
            'hours_worked' => 8.0,
            'late_minutes' => 0,
            'unpaid_leave_days' => 1.0,
        ])
        ->and($pay['basicPay'])->toBe(650.0)
        ->and($payWithTwoPaidDays['basicPay'] - $pay['basicPay'])->toBe(650.0)
        ->and($pay['breakdown'])->not->toHaveKey('unpaid_leave')
        ->and(array_keys($pay['breakdown']))->toBe([
            'malasakitan', 'late', 'absences', 'sss', 'philhealth', 'hdmf',
            'peraa_ceap', 'sss_loan', 'philhealth_loan', 'peraa_ceap_loan',
            'hdmf_loan', 'cash_advance',
        ]);
});

test('payroll attendance ranges follow the 5th and 20th cutoffs', function () {
    $controller = new PayrollProcessingController;
    $range = new ReflectionMethod($controller, 'cutoffDateRange');

    expect($range->invoke($controller, '2026-02', '15th'))
        ->toBe(['2026-01-21', '2026-02-05'])
        ->and($range->invoke($controller, '2026-02', '30th'))
        ->toBe(['2026-02-06', '2026-02-20']);
});

test('15th payroll attendance range crosses year boundary correctly', function () {
    $controller = new PayrollProcessingController;
    $range = new ReflectionMethod($controller, 'cutoffDateRange');

    expect($range->invoke($controller, '2026-01', '15th'))
        ->toBe(['2025-12-21', '2026-01-05']);
});

test('payroll deducts 1.80 for every recorded late minute', function () {
    $result = calculatePayrollProcessingRow('15th', 5, 175.98);

    expect($result['lateMinutes'])->toBe(5.0)
        ->and($result['breakdown']['late'])->toBe(9.0);
});

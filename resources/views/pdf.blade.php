@php
    $peso = fn ($v) => '₱' . number_format((float) $v, 2);
    $labels = [
        'malasakitan' => 'MALASAKITAN', 'late' => 'Late', 'absences' => 'Absences', 'sss' => 'SSS',
        'sss_loan' => 'SSS Loan', 'philhealth' => 'PhilHealth', 'peraa_ceap' => 'PERAA/CEAP',
        'peraa_ceap_loan' => 'PERAA/CEAP Loan', 'hdmf' => 'HDMF', 'hdmf_loan' => 'HDMF Loan',
        'cash_advance' => 'Cash advance',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payslip</title>
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
    h1 { font-size: 16px; margin: 0; color: #b22222; }
    .muted { color: #6b7280; }
    .head { border-bottom: 2px solid #b22222; padding-bottom: 8px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 4px 6px; border: 1px solid #e5e7eb; }
    .cols td.col { width: 50%; vertical-align: top; padding: 0 6px; }
    .row td { padding: 3px 0; }
    .row td:last-child { text-align: right; font-weight: bold; }
    .total td { border-top: 1px solid #d1d5db; padding-top: 5px; font-weight: bold; }
    h3 { font-size: 11px; color: #b22222; text-transform: uppercase; margin: 12px 0 4px; }
    .net { background: #b22222; color: #fff; padding: 8px 10px; margin-top: 14px; }
    .net td { color: #fff; font-weight: bold; font-size: 13px; }
    .net td:last-child { text-align: right; }
    .foot { margin-top: 16px; font-size: 9px; }
</style>
</head>
<body>
    <div class="head">
        <h1>St. Mary Magdalene School: Payslip</h1>
        <div class="muted">{{ $card['employee_name'] }} | ID: {{ $card['employee_number'] }}</div>
        <div class="muted">{{ $card['department'] }} | {{ $card['position'] }} | {{ $card['step_name'] }}</div>
    </div>

    <table class="meta">
        <tr>
            <td><span class="muted">Pay period</span><br><strong>{{ $card['cutoff'] }}</strong></td>
            <td><span class="muted">Transfer date</span><br><strong>{{ $payslip->transfer_date?->format('M d, Y') ?? '-' }}</strong></td>
            <td><span class="muted">Days worked</span><br><strong>{{ $card['days_worked'] }}</strong></td>
            <td><span class="muted">Hours worked</span><br><strong>{{ $card['hours_worked'] }}</strong></td>
            <td><span class="muted">Late (mins)</span><br><strong>{{ $card['late_minutes'] }}</strong></td>
        </tr>
    </table>

    <table class="cols">
        <tr>
            <td class="col">
                <h3>Earnings</h3>
                <table>
                    <tr class="row"><td>Basic salary</td><td>{{ $peso($card['basic_pay']) }}</td></tr>
                    <tr class="row"><td>Daily rate</td><td>{{ $peso($card['daily_wage']) }}</td></tr>
                    <tr class="row"><td>Honorarium</td><td>{{ $peso($card['honorarium']) }}</td></tr>
                    <tr class="total"><td>Gross earnings</td><td style="text-align:right">{{ $peso($card['gross_pay']) }}</td></tr>
                </table>
            </td>
            <td class="col">
                <h3>Deductions</h3>
                <table>
                    @foreach ($card['deductions'] as $key => $amount)
                        <tr class="row"><td>{{ $labels[$key] ?? $key }}</td><td>{{ $peso($amount) }}</td></tr>
                    @endforeach
                    <tr class="total"><td>Total deductions</td><td style="text-align:right">{{ $peso($card['total_deductions']) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="net"><tr><td>NET PAY</td><td>{{ $peso($card['net_pay']) }}</td></tr></table>

    <p class="foot muted">
        Approved by employee on {{ $payslip->response?->reviewed_at?->format('M d, Y h:i A') }}.
        This is a system-generated payslip. Confidential: for the named employee only.
    </p>
</body>
</html>
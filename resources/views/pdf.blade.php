@php
    $peso = fn ($v) => '₱' . number_format((float) $v, 2);

    // Monthly wage = daily wage x 26
    $monthlyWage = (float) $card['daily_wage'] * 26;

    // "15th" -> "15th Pay Period", "30th" -> "30th Pay Period"
    $cutoff      = trim((string) ($card['cutoff'] ?? $payslip->cutoff));
    $payPeriod   = $cutoff === '' ? '-' : $cutoff . ' Pay Period';
    $is30        = $cutoff === '30th';

    // Honorarium (administrative role only, fixed P1,924.31):
    // use the card value, then fall back to the saved payslip column
    $honorarium = (float) ($card['honorarium'] ?? $payslip->honorarium ?? 0);

    // PhilHealth loan: read safely so the PDF still works before the column exists
    $deds = $card['deductions'] ?? [];
    $deds['philhealth_loan'] = $deds['philhealth_loan']
        ?? ($payslip->getAttributes()['philhealth_loan'] ?? 0);

    // [label, key, only on the 30th pay period]
    // HDMF is a fixed 200 and is shown on every pay period.
    $deductionRows = [
        ['MALASAKITAN',     'malasakitan',     false],
        ['Late',            'late',            false],
        ['Absences',        'absences',        false],
        ['SSS',             'sss',             true],
        ['PhilHealth',      'philhealth',      true],
        ['HDMF',            'hdmf',            false],
        ['PERAA/CEAP',      'peraa_ceap',      true],
        ['SSS Loan',        'sss_loan',        true],
        ['PhilHealth Loan', 'philhealth_loan', true],
        ['PERAA/CEAP Loan', 'peraa_ceap_loan', true],
        ['HDMF Loan',       'hdmf_loan',       true],
        ['Cash advance',    'cash_advance',    false],
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

    /* Signature block */
    .signature { margin-top: 40px; }
    .signature td.sig-spacer { width: 60%; }
    .signature td.sig-box { width: 40%; text-align: center; vertical-align: bottom; }
    .sig-label { text-align: left; font-size: 11px; font-weight: bold; margin-bottom: 36px; }
    .sig-line { border-top: 1px solid #1f2937; padding-top: 4px; font-size: 10px; font-weight: bold; }
    .sig-role { font-size: 9px; color: #6b7280; }
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
            <td><span class="muted">Pay period</span><br><strong>{{ $payPeriod }}</strong></td>
            <td><span class="muted">Transfer date</span><br><strong>{{ $payslip->transfer_date?->format('M d, Y') ?? '-' }}</strong></td>
            <td><span class="muted">Days worked</span><br><strong>{{ $card['days_worked'] }}</strong></td>
            <td><span class="muted">Hours worked</span><br><strong>{{ $card['hours_worked'] }}</strong></td>
            <td><span class="muted">Late (mins)</span><br><strong>{{ $card['late_minutes'] }}</strong></td>
            <td><span class="muted">Monthly wage</span><br><strong>{{ $peso($monthlyWage) }}</strong></td>
        </tr>
    </table>

    <table class="cols">
        <tr>
            <td class="col">
                <h3>Earnings</h3>
                <table>
                    <tr class="row"><td>Basic salary</td><td>{{ $peso($card['basic_pay']) }}</td></tr>
                    <tr class="row"><td>Daily rate</td><td>{{ $peso($card['daily_wage']) }}</td></tr>
                    <tr class="row"><td>Honorarium</td><td>{{ $honorarium > 0 ? $peso($honorarium) : '-' }}</td></tr>
                    <tr class="total"><td>Gross earnings</td><td style="text-align:right">{{ $peso($card['gross_pay']) }}</td></tr>
                </table>
            </td>
            <td class="col">
                <h3>Deductions</h3>
                <table>
                    {{-- SSS, PhilHealth, PERAA/CEAP and the loans are deducted on the 30th pay period only. HDMF (fixed 200) is deducted every pay period. --}}
                    @foreach ($deductionRows as [$label, $key, $only30])
                        <tr class="row">
                            <td>{{ $label }}</td>
                            <td>{{ ($only30 && ! $is30) ? '-' : $peso($deds[$key] ?? 0) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total"><td>Total deductions</td><td style="text-align:right">- {{ $peso($card['total_deductions']) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="net"><tr><td>NET PAY</td><td>{{ $peso($card['net_pay']) }}</td></tr></table>

    {{-- Approved By signature (right side) --}}
    <table class="signature">
        <tr>
            <td class="sig-spacer"></td>
            <td class="sig-box">
                <div class="sig-label">Approved By:</div>
                <div class="sig-line">
                    {{ $approver_name ?? '' }}
                </div>
                <div class="sig-role">{{ $approver_position ?? 'Authorized Signatory' }}</div>
            </td>
        </tr>
    </table>

    <p class="foot muted">
        Approved by employee on {{ $payslip->response?->reviewed_at?->format('M d, Y h:i A') }}.
        This is a system-generated payslip. Confidential: for the named employee only.
    </p>
</body>
</html>
{{-- resources/views/payroll/index.blade.php --}}
{{-- NOTE: if your layout component is not <x-app-layout>, change the opening/closing tag below. --}}

    {{-- Browser tab title --}}
    @auth
    <x-slot name="title">
        @if (auth()->user()->hasRole('Admin'))
            Admin Payroll Management
        @elseif (auth()->user()->hasRole('HR'))
            HR Payroll Management
        @elseif (auth()->user()->hasRole('Accounting'))
            Accounting Payroll Management
        @elseif (auth()->user()->hasRole('Employee'))
            Employee Payroll Management
        @elseif (auth()->user()->hasRole('Applicant'))
            Applicant Dashboard
        @else
            Dashboard
        @endif
    </x-slot>
@endauth

    {{-- Page heading (shown in the layout's top bar) --}}
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none ml-[80px] tracking-tight text-[#b22222] sm:text-4xl">Payroll Management</h1>
    </x-slot>

    @php
        // Approval badge look (Admin / HR / Employee)
        $badge = fn (string $state) => match ($state) {
            'Approved'     => 'bg-[#ecfdf5] text-[#166534]',
            'Rejected'     => 'bg-[#fef2f2] text-[#b91c1c]',
            'Not released' => 'bg-gray-100 text-gray-600',
            default        => 'bg-[#fff7ed] text-[#9a5b00]',
        };

        $peso = fn ($n) => '₱' . number_format((float) $n, 2);

        // Row look inside the payslip modal
        $row = 'flex min-h-[30px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.85rem] text-gray-500';

        $allReviewed = $summary['total'] > 0 && $summary['pending'] === 0;

        // HR reviews the HR stage; other roles review the Admin stage.
        $isHR          = auth()->check() && auth()->user()->hasRole('HR');
        $stage         = $isHR ? \App\Models\Payslip::STAGE_HR : \App\Models\Payslip::STAGE_ADMIN;
        $stageLabel    = $isHR ? 'Accounting to HR Approval' : 'Accounting to Admin Approval';
        $approvalLabel = $isHR ? 'Pending HR Review' : 'Pending Admin Review';
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="payrollPage(@js([
            'slips'    => $cards,
            'reopen'   => $errors->has('reason'),
            'rejectId' => old('payslip_id'),
            'reason'   => old('reason', ''),
            'downloadUrl' => route('payslips.download', ['payslip' => '__ID__']),
        ]))"
        @keydown.escape.window="viewId = null; rejectId = null"
    >
        <section aria-labelledby="payrollManagementTitle"
            class="rounded-2xl border border-[#ecebea] bg-white p-5 sm:p-6">

            {{-- Heading + actions --}}
            <div class="mb-[22px] flex flex-col items-start justify-between gap-[18px] lg:flex-row lg:items-center">
                <div>
                    <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">{{ $stageLabel }}</p>
                    <h2 id="payrollManagementTitle" class="text-[clamp(1.5rem,1.8vw,2.2rem)] font-bold text-gray-900">Salary Monitoring Approval Queue</h2>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2.5 lg:w-auto lg:justify-end">
                    <form method="POST" action="{{ route('payroll.approve-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" @disabled($summary['approvable'] === 0)
                            title="{{ ! $isHR && $summary['pending'] > 0 && $summary['approvable'] === 0 ? 'HR approval is required first' : '' }}"
                            class="cursor-pointer rounded-xl bg-[#b22222] px-4 py-[11px] font-bold text-white transition hover:-translate-y-px disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0">
                            {{ $allReviewed ? 'Reviewed' : (! $isHR && $summary['pending'] > 0 && $summary['approvable'] === 0 ? 'Awaiting HR approval' : 'Approve all') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->has('payslip'))
                <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    {{ $errors->first('payslip') }}
                </div>
            @endif

            {{-- Summary cards --}}
            <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
                @foreach ([
                    ['Total Payslips', $summary['total'],    'border-[#eef1f4] bg-[#f9fafb]'],
                    [$approvalLabel,   $summary['pending'],  'border-[#fed7aa] bg-[#fff7ed]'],
                    ['Approved',       $summary['approved'], 'border-[#a7f3d0] bg-[#ecfdf5]'],
                    ['Rejected',       $summary['rejected'], 'border-[#fecaca] bg-[#fef2f2]'],
                ] as [$label, $count, $look])
                    <article class="flex items-center justify-between gap-3 rounded-xl border px-4 py-3 {{ $look }}">
                        <span class="text-xs font-bold uppercase tracking-[0.04em] text-gray-500">{{ $label }}</span>
                        <strong class="text-xl leading-none text-gray-900">{{ $count }}</strong>
                    </article>
                @endforeach
            </div>

            {{-- Payslip rows --}}
            <div class="grid gap-3" aria-live="polite">
                @forelse ($payslips as $slip)
                    @php
                        // Normalized (case-insensitive) states from the model
                        $myStatus   = $slip->stageStatus($stage);                              // pending|approved|rejected
                        $adminState = ucfirst($slip->stageStatus(\App\Models\Payslip::STAGE_ADMIN));
                        $hrState    = ucfirst($slip->stageStatus(\App\Models\Payslip::STAGE_HR));
                        $empState   = ! $slip->isReleased()
                            ? 'Not released'
                            : ($slip->response?->status ?? 'Pending');
                    @endphp

                    <article class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                        <div class="grid items-center gap-3 lg:grid-cols-[minmax(0,1.6fr)_minmax(110px,0.7fr)_minmax(170px,0.8fr)]">
                            <div>
                                <h3 class="text-[1.05rem] font-bold text-gray-900">{{ $slip->employee?->name ?? 'Unknown employee' }}</h3>
                                <p class="mt-1 text-[0.85rem] text-gray-500">
                                    {{ $slip->employee?->employee_id ?? '-' }} •
                                    {{ $slip->employee ? (\App\Models\Employee::DEPARTMENTS[$slip->employee->department] ?? $slip->employee->department) : '-' }}
                                </p>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[0.72rem] uppercase tracking-[0.08em] text-gray-500">Net pay</span>
                                <strong class="text-[1.05rem] text-gray-900">{{ $peso($slip->net_pay) }}</strong>
                            </div>
                            <div class="flex flex-col gap-1 lg:items-end lg:text-right">
                                <span class="text-[0.72rem] uppercase tracking-[0.08em] text-gray-500">Date</span>
                                <strong class="whitespace-nowrap text-[1.05rem] text-gray-900">{{ $slip->cutoffDateLabel() }}</strong>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-col items-start justify-between gap-3 border-t border-gray-200 pt-3 lg:flex-row lg:items-center">
                            {{-- Approval badges --}}
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-[7px] text-xs font-bold {{ $badge($adminState) }}"
                                    @if ($adminState === 'Rejected' && $slip->admin_rejection_reason) title="{{ $slip->admin_rejection_reason }}" @endif>
                                    Admin: {{ $adminState }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-[7px] text-xs font-bold {{ $badge($hrState) }}"
                                    @if ($hrState === 'Rejected' && $slip->hr_rejection_reason) title="{{ $slip->hr_rejection_reason }}" @endif>
                                    HR: {{ $hrState }}
                                </span>
                                {{-- Employee badge reflects the employee's actual answer --}}
                                <span class="inline-flex items-center rounded-full px-2.5 py-[7px] text-xs font-bold {{ $badge($empState) }}"
                                    @if ($empState === 'Rejected' && $slip->response?->reason) title="{{ $slip->response->reason }}" @endif>
                                    Employee: {{ $empState }}
                                </span>
                            </div>

                            {{-- Row actions --}}
                            <div class="flex w-full flex-wrap items-center gap-2.5 lg:w-auto">
                                @if ($myStatus === 'approved')
                                    <button type="button" disabled
                                        class="cursor-not-allowed rounded-[10px] border border-[#15803d] bg-[#15803d] px-3 py-[9px] text-[0.8rem] font-bold text-white opacity-70">Approved</button>
                                @elseif ($myStatus === 'rejected')
                                    <button type="button" disabled
                                        class="cursor-not-allowed rounded-[10px] border border-[#b91c1c] bg-[#b91c1c] px-3 py-[9px] text-[0.8rem] font-bold text-white opacity-70">Rejected</button>
                                @elseif (! $isHR && $hrState === 'Pending')
                                    <button type="button" disabled title="HR must approve or reject this payslip first"
                                        class="cursor-not-allowed rounded-[10px] border border-gray-300 bg-gray-300 px-3 py-[9px] text-[0.8rem] font-bold text-gray-600">
                                        Awaiting HR review
                                    </button>
                                    <button type="button" disabled title="HR must approve or reject this payslip first"
                                        class="cursor-not-allowed rounded-[10px] border border-gray-300 bg-gray-300 px-3 py-[9px] text-[0.8rem] font-bold text-gray-600">
                                        Reject
                                    </button>
                                @else
                                    @if (! $isHR && $hrState !== 'Approved')
                                        <button type="button" disabled title="HR approval is required first"
                                            class="cursor-not-allowed rounded-[10px] border border-gray-300 bg-gray-300 px-3 py-[9px] text-[0.8rem] font-bold text-gray-600">
                                            Awaiting HR approval
                                        </button>
                                    @else
                                        <form method="POST" action="{{ route('payroll.approve') }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="payslip_id" value="{{ $slip->id }}">
                                            <button type="submit"
                                                class="cursor-pointer rounded-[10px] border border-[#15803d] bg-[#15803d] px-3 py-[9px] text-[0.8rem] font-bold text-white transition hover:-translate-y-px">Approve</button>
                                        </form>
                                    @endif
                                    <button type="button" @click="openReject({{ $slip->id }})"
                                        class="cursor-pointer rounded-[10px] border border-[#b91c1c] bg-[#b91c1c] px-3 py-[9px] text-[0.8rem] font-bold text-white transition hover:-translate-y-px">Reject</button>
                                @endif

                                <button type="button" @click="viewId = {{ $slip->id }}"
                                    class="cursor-pointer rounded-[10px] border border-[#b22222] bg-transparent px-3 py-[9px] text-[0.8rem] font-bold text-[#b22222] transition hover:-translate-y-px">View payslip</button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[18px] border border-dashed border-gray-900 bg-white px-5 py-8 text-center font-semibold text-gray-500">
                        No payroll submissions have been transferred from Accounting yet.
                    </div>
                @endforelse
            </div>

            @if ($payslips->hasPages())
                <div class="mt-5">{{ $payslips->links() }}</div>
            @endif
        </section>

        {{-- ============ Payslip modal ============ --}}
        <div x-show="slip" x-cloak x-transition.opacity @click.self="viewId = null"
            class="fixed inset-0 z-[1200] grid place-items-center bg-[rgba(17,24,39,0.48)] p-3">
            <section role="dialog" aria-modal="true" aria-labelledby="payrollPayslipTitle"
                class="relative w-[min(720px,calc(100vw-28px))] rounded-[24px] bg-white px-2.5 pb-2.5 pt-1.5 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
                <button type="button" @click="viewId = null" aria-label="Close payslip"
                    class="absolute right-3 top-3 grid h-8 w-8 cursor-pointer place-items-center rounded-full text-xl text-gray-500 transition hover:bg-[#fef2f2] hover:text-[#b22222]">
                    &times;
                </button>

                <template x-if="slip">
                    <div class="bg-white">
                        <div class="flex flex-col items-center gap-0.5 px-3 pb-3 pt-2 text-center">
                            <strong id="payrollPayslipTitle" class="text-[clamp(1.35rem,2vw,2rem)] font-extrabold leading-tight text-gray-900" x-text="slip.employee_name"></strong>
                            <span class="text-[0.8rem] text-gray-800" x-text="'ID: ' + slip.employee_number"></span>
                            <span class="text-[0.8rem] text-gray-500" x-text="slip.department + ' | ' + slip.position + ' | ' + slip.step_name"></span>
                        </div>

                        <div class="grid grid-cols-2 border-y border-[#ecebea] sm:grid-cols-5">
                            <template x-for="(m, k) in [
                                ['Date', slip.cutoff_label],
                                ['Days worked', Number(slip.days_worked)],
                                ['Hours worked', Number(slip.hours_worked)],
                                ['Late (mins)', slip.late_minutes],
                                ['Monthly wage', money(slip.monthly_wage)]
                            ]" :key="k">
                                <div class="grid justify-items-center gap-[3px] px-1 py-2 text-center" :class="k > 0 ? 'sm:border-l sm:border-[#ecebea]' : ''">
                                    <span class="text-[0.62rem] font-extrabold uppercase tracking-[0.08em] text-gray-500" x-text="m[0]"></span>
                                    <strong class="text-[0.9rem] text-gray-900" x-text="m[1]"></strong>
                                </div>
                            </template>
                        </div>

                        <div class="grid sm:grid-cols-2">
                            <section class="min-w-0 border-t border-[#ecebea] p-2.5">
                                <h3 class="mb-2 text-[0.72rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Earnings</h3>
                                <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.8rem] text-gray-500">
                                    <span>Basic salary</span>
                                    <strong class="min-w-[110px] text-center text-gray-900" x-text="money(slip.basic_pay)"></strong>
                                </div>
                                <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.8rem] text-gray-500">
                                    <span>Daily rate</span>
                                    <strong class="min-w-[110px] text-center text-gray-900" x-text="money(slip.daily_wage)"></strong>
                                </div>
                                <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.8rem] text-gray-500">
                                    <span>Honorarium</span>
                                    <strong class="min-w-[110px] text-center text-gray-900" x-text="Number(slip.honorarium) > 0 ? money(slip.honorarium) : '-'"></strong>
                                </div>
                                <div class="mt-2 flex min-h-[28px] items-center justify-between gap-3 text-[0.8rem] font-extrabold text-gray-900">
                                    <span>Gross earnings</span>
                                    <strong class="min-w-[110px] text-center text-[#b22222]" x-text="money(slip.gross_pay)"></strong>
                                </div>
                            </section>

                            <section class="min-w-0 border-t border-[#ecebea] p-2.5 sm:border-l">
                                <h3 class="mb-2 text-[0.72rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Deductions</h3>
                                <template x-for="r in deductionRows" :key="r[1]">
                                    <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.8rem] text-gray-500">
                                        <span x-text="r[0]"></span>
                                        <span class="min-w-[110px] text-center font-bold text-gray-900"
                                            x-text="(r[2] && slip.cutoff !== '30th') ? '-' : money(slip.deductions[r[1]])"></span>
                                    </div>
                                </template>
                                <div class="mt-2 flex min-h-[28px] items-center justify-between gap-3 text-[0.8rem] font-extrabold text-gray-900">
                                    <span>Total deductions</span>
                                    <strong class="min-w-[110px] text-center text-[#b22222]" x-text="'- ' + money(slip.total_deductions)"></strong>
                                </div>
                            </section>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t-2 border-[#b22222] bg-[#fef2f2] px-4 py-2.5 font-black tracking-[0.08em] text-[#b22222]">
                            <span>NET PAY</span>
                            <strong class="text-base tracking-normal" x-text="money(slip.net_pay)"></strong>
                        </div>
                    </div>
                </template>

                <div class="mt-3 flex items-center justify-between gap-2.5">
                    <template x-if="slip && slip.employee_status === 'Approved'">
                        <a :href="downloadUrl.replace('__ID__', slip.id)"
                            class="inline-flex flex-end cursor-pointer items-center gap-2 rounded-[10px] border border-[#b22222] bg-[#b22222] px-4 py-[10px] text-[0.85rem] font-bold text-white transition hover:-translate-y-px">
                            <i class="fa-solid fa-download" aria-hidden="true"></i> Download payslip (PDF)
                        </a>
                    </template>
                </div>
            </section>
        </div>

        {{-- ============ Rejection modal ============ --}}
        <div x-show="rejectId" x-cloak x-transition.opacity @click.self="rejectId = null"
            class="fixed inset-0 z-[70] grid place-items-center bg-[rgba(15,23,42,0.5)] p-5">
            <section role="dialog" aria-modal="true" aria-labelledby="payrollRejectionTitle"
                class="w-[min(480px,calc(100vw-32px))] rounded-[22px] bg-white p-[26px] shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
                <h2 id="payrollRejectionTitle" class="text-[1.35rem] font-bold text-gray-900">Reject payslip</h2>
                <p class="mb-5 mt-2 text-gray-500">Please provide a reason for rejecting this payslip.</p>

                <form method="POST" action="{{ route('payroll.reject') }}" class="grid gap-2.5">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="payslip_id" :value="rejectId">

                    <label for="payrollRejectionReason" class="text-[0.9rem] font-bold text-gray-700">Reason</label>
                    <textarea id="payrollRejectionReason" name="reason" rows="4" maxlength="1000" required x-model="reason"
                        class="min-h-[110px] w-full resize-y rounded-[10px] border border-gray-300 px-3 py-[11px] text-gray-900 focus:border-[#b91c1c] focus:outline-none focus:ring-[3px] focus:ring-[#fecaca]"></textarea>
                    <p class="text-xs font-semibold text-red-600">{{ $errors->first('reason') }}</p>

                    <div class="mt-2 flex justify-end gap-2.5">
                        <button type="button" @click="rejectId = null"
                            class="cursor-pointer rounded-[10px] border border-[#b22222] bg-transparent px-3 py-[9px] text-[0.8rem] font-bold text-[#b22222] transition hover:-translate-y-px">Cancel</button>
                        <button type="submit"
                            class="cursor-pointer rounded-[10px] border border-[#b91c1c] bg-[#b91c1c] px-3 py-[9px] text-[0.8rem] font-bold text-white transition hover:-translate-y-px">Confirm rejection</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        function payrollPage(cfg) {
            return {
                slips: cfg.slips,
                viewId: null,
                rejectId: cfg.reopen ? cfg.rejectId : null,
                reason: cfg.reopen ? cfg.reason : '',
                downloadUrl: cfg.downloadUrl,

                // [label, key in slip.deductions, only on the 30th pay period]
                deductionRows: [
                    ['MALASAKITAN', 'malasakitan', false],
                    ['Late', 'late', false],
                    ['SSS', 'sss', true],
                    ['PhilHealth', 'philhealth', true],
                    ['HDMF', 'hdmf', true],
                    ['PERAA/CEAP', 'peraa_ceap', true],
                    ['SSS Loan', 'sss_loan', true],
                    ['PhilHealth Loan', 'philhealth_loan', true],
                    ['PERAA/CEAP Loan', 'peraa_ceap_loan', true],
                    ['HDMF Loan', 'hdmf_loan', true],
                    ['Cash advance', 'cash_advance', false],
                ],

                get slip() {
                    return this.viewId ? this.slips[this.viewId] : null;
                },

                openReject(id) {
                    this.reason = '';
                    this.rejectId = id;
                },

                money(value) {
                    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0);
                },
            };
        }
    </script>
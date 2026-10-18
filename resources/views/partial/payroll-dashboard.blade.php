{{-- resources/views/payroll/index.blade.php --}}


    {{-- Browser tab title --}}
    <x-slot name="title">
        Payroll Dashboard
    </x-slot>

    {{-- Page heading (shown in the layout's top bar) --}}
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none ml-[80px] tracking-tight text-[#b22222] sm:text-4xl">Payroll Management</h1>
    </x-slot>

    @php
        // Approval badge look (Admin / HR / Employee)
        $badge = fn (string $state) => match ($state) {
            'Approved' => 'bg-[#ecfdf5] text-[#166534]',
            'Rejected' => 'bg-[#fef2f2] text-[#b91c1c]',
            default    => 'bg-[#fff7ed] text-[#9a5b00]',
        };

        $peso = fn ($n) => '₱' . number_format((float) $n, 2);

        $allReviewed = $summary['total'] > 0 && $summary['pending'] === 0;
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="payrollPage(@js([
            'slips'    => $cards,
            'reopen'   => $errors->has('reason'),
            'rejectId' => old('payslip_id'),
            'reason'   => old('reason', ''),
        ]))"
        @keydown.escape.window="viewId = null; rejectId = null"
    >
        <section aria-labelledby="payrollManagementTitle"
            class="rounded-[26px] border border-[#f4e5e5] bg-white p-[clamp(18px,2vw,28px)] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

            {{-- Heading + actions --}}
            <div class="mb-[22px] flex flex-col items-start justify-between gap-[18px] lg:flex-row lg:items-center">
                <div>
                    <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Accounting to Admin approval</p>
                    <h2 id="payrollManagementTitle" class="text-[clamp(1.5rem,1.8vw,2.2rem)] font-bold text-gray-900">Salary Monitoring Approval Queue</h2>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2.5 lg:w-auto lg:justify-end">
                    <span class="inline-flex items-center rounded-full bg-[#fff7ed] px-3.5 py-[9px] text-[0.8rem] font-bold text-[#9a5b00]">
                        {{ $statusText }}
                    </span>

                    <form method="POST" action="{{ route('payroll.approve-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" @disabled($summary['total'] === 0 || $allReviewed)
                            class="cursor-pointer rounded-xl bg-[#b22222] px-4 py-[11px] font-bold text-white transition hover:-translate-y-px disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0">
                            {{ $allReviewed ? 'Reviewed' : 'Approve all' }}
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
            <div class="mb-[22px] grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['Total Payslips',        $summary['total'],    'border-[#eef1f4] bg-[#f9fafb]'],
                    ['Pending Admin Review',  $summary['pending'],  'border-[#fed7aa] bg-[#fff7ed]'],
                    ['Approved',              $summary['approved'], 'border-[#a7f3d0] bg-[#ecfdf5]'],
                    ['Rejected',              $summary['rejected'], 'border-[#fecaca] bg-[#fef2f2]'],
                ] as [$label, $count, $look])
                    <article class="flex flex-col gap-2 rounded-[18px] border px-5 py-[18px] {{ $look }}">
                        <span class="text-[0.8rem] font-bold uppercase tracking-[0.06em] text-gray-500">{{ $label }}</span>
                        <strong class="text-[clamp(1.6rem,2vw,2.3rem)] leading-none text-gray-900">{{ $count }}</strong>
                    </article>
                @endforeach
            </div>

            {{-- Payslip rows --}}
            <div class="grid gap-4" aria-live="polite">
                @forelse ($payslips as $slip)
                    <article class="rounded-[18px] border border-black/30 bg-white px-[18px] pb-3.5 pt-[18px]">
                        <div class="grid items-center gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(110px,0.7fr)_minmax(90px,0.45fr)]">
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
                            <div class="flex flex-col gap-1">
                                <span class="text-[0.72rem] uppercase tracking-[0.08em] text-gray-500">Cutoff</span>
                                <strong class="text-[1.05rem] text-gray-900">{{ $slip->cutoff }}</strong>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col items-start justify-between gap-4 border-t border-black/50 pt-4 lg:flex-row lg:items-center">
                            {{-- Approval badges --}}
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-[7px] text-xs font-bold {{ $badge($slip->admin_status) }}"
                                    @if ($slip->admin_status === 'Rejected' && $slip->admin_rejection_reason) title="{{ $slip->admin_rejection_reason }}" @endif>
                                    Admin: {{ $slip->admin_status }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-[7px] text-xs font-bold {{ $badge($slip->hr_status) }}"
                                    @if ($slip->hr_status === 'Rejected' && $slip->hr_rejection_reason) title="{{ $slip->hr_rejection_reason }}" @endif>
                                    HR: {{ $slip->hr_status }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-[7px] text-xs font-bold {{ $badge($slip->released_at ? 'Approved' : 'Pending') }}">
                                    Employee: {{ $slip->released_at ? 'Approved' : 'Pending' }}
                                </span>
                            </div>

                            {{-- Row actions --}}
                            <div class="flex w-full flex-wrap items-center gap-2.5 lg:w-auto">
                                @if ($slip->admin_status === 'Approved')
                                    <button type="button" disabled
                                        class="cursor-not-allowed rounded-[10px] border border-[#15803d] bg-[#15803d] px-3 py-[9px] text-[0.8rem] font-bold text-white opacity-70">Approved</button>
                                @elseif ($slip->admin_status === 'Rejected')
                                    <button type="button" disabled
                                        class="cursor-not-allowed rounded-[10px] border border-[#b91c1c] bg-[#b91c1c] px-3 py-[9px] text-[0.8rem] font-bold text-white opacity-70">Rejected</button>
                                @else
                                    <form method="POST" action="{{ route('payroll.approve') }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="payslip_id" value="{{ $slip->id }}">
                                        <button type="submit"
                                            class="cursor-pointer rounded-[10px] border border-[#15803d] bg-[#15803d] px-3 py-[9px] text-[0.8rem] font-bold text-white transition hover:-translate-y-px">Approve</button>
                                    </form>
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
            class="fixed inset-0 z-[60] grid place-items-center bg-[rgba(15,23,42,0.5)] p-5">
            <section role="dialog" aria-modal="true" aria-labelledby="payrollPayslipTitle"
                class="relative max-h-[calc(100vh-24px)] w-[min(900px,calc(100vw-32px))] overflow-y-auto rounded-[22px] bg-white px-3 pb-3 pt-[34px] shadow-[0_20px_50px_rgba(15,23,42,0.08)]">

                <button type="button" @click="viewId = null" aria-label="Close payslip"
                    class="absolute right-[18px] top-4 grid h-[38px] w-[38px] cursor-pointer place-items-center rounded-xl bg-gray-100 text-gray-900 transition hover:bg-gray-200">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

                <template x-if="slip">
                    <div class="grid gap-[18px] p-2 text-gray-900 sm:p-4">

                        {{-- Header --}}
                        <header class="flex flex-col items-center gap-1 border-b border-gray-200 pb-2.5 text-center">
                            <strong id="payrollPayslipTitle" class="text-2xl" x-text="slip.employee_name"></strong>
                            <span class="text-gray-500" x-text="'ID: ' + slip.employee_number"></span>
                            <small class="text-gray-500" x-text="slip.department + ' | ' + slip.position + ' | ' + slip.step_name"></small>
                        </header>

                        {{-- Attendance summary --}}
                        <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 sm:grid-cols-4">
                            <div class="flex flex-col items-center justify-center gap-1.5 bg-gray-50 px-2.5 py-3 text-center">
                                <span class="text-[0.72rem] font-bold uppercase tracking-[0.05em] text-gray-500">Days worked</span>
                                <strong class="text-xl" x-text="Number(slip.days_worked)"></strong>
                            </div>
                            <div class="flex flex-col items-center justify-center gap-1.5 bg-gray-50 px-2.5 py-3 text-center">
                                <span class="text-[0.72rem] font-bold uppercase tracking-[0.05em] text-gray-500">Hours worked</span>
                                <strong class="text-xl" x-text="Number(slip.hours_worked)"></strong>
                            </div>
                            <div class="flex flex-col items-center justify-center gap-1.5 bg-gray-50 px-2.5 py-3 text-center">
                                <span class="text-[0.72rem] font-bold uppercase tracking-[0.05em] text-gray-500">Late (mins)</span>
                                <strong class="text-xl" x-text="slip.late_minutes"></strong>
                            </div>
                            <div class="flex flex-col items-center justify-center gap-1.5 bg-gray-50 px-2.5 py-3 text-center">
                                <span class="text-[0.72rem] font-bold uppercase tracking-[0.05em] text-gray-500">Cutoff</span>
                                <strong class="text-xl" x-text="slip.cutoff"></strong>
                            </div>
                        </div>

                        {{-- Earnings + deductions --}}
                        <div class="grid overflow-hidden rounded-[14px] border border-gray-200 sm:grid-cols-2">
                            <section class="min-w-0 content-start p-[18px]">
                                <h3 class="mb-1 text-[0.95rem] font-bold uppercase tracking-[0.04em] text-[#b22222]">Earnings</h3>
                                <div class="flex items-center justify-between gap-2.5 rounded-[10px] bg-gray-50 px-3 py-2.5"><span class="text-[0.8rem] text-gray-500">Basic salary</span><strong class="text-[0.95rem]" x-text="money(slip.basic_pay)"></strong></div>
                                <div class="mt-1 flex items-center justify-between gap-2.5 rounded-[10px] bg-gray-50 px-3 py-2.5"><span class="text-[0.8rem] text-gray-500">Daily rate</span><strong class="text-[0.95rem]" x-text="money(slip.daily_wage)"></strong></div>
                                <div class="mt-1 flex items-center justify-between gap-2.5 rounded-[10px] bg-gray-50 px-3 py-2.5"><span class="text-[0.8rem] text-gray-500">Honorarium</span><strong class="text-[0.95rem]" x-text="money(slip.honorarium)"></strong></div>
                                <div class="mt-1.5 flex items-center justify-between gap-2.5 border-t border-gray-200 pt-2.5">
                                    <span class="text-[0.8rem] font-extrabold text-gray-500">Gross earnings</span>
                                    <strong class="text-[0.95rem] font-extrabold text-[#b22222]" x-text="money(slip.gross_pay)"></strong>
                                </div>
                            </section>

                            <section class="min-w-0 content-start border-t border-gray-200 p-[18px] sm:border-l sm:border-t-0">
                                <h3 class="mb-1 text-[0.95rem] font-bold uppercase tracking-[0.04em] text-[#b22222]">Deductions</h3>
                                <template x-for="row in deductionRows" :key="row[1]">
                                    <div class="mt-1 flex items-center justify-between gap-2.5 rounded-[10px] bg-gray-50 px-3 py-2.5">
                                        <span class="text-[0.8rem] text-gray-500" x-text="row[0]"></span>
                                        <strong class="text-[0.95rem]" x-text="money(slip.deductions[row[1]])"></strong>
                                    </div>
                                </template>
                                <div class="mt-1.5 flex items-center justify-between gap-2.5 border-t border-gray-200 pt-2.5">
                                    <span class="text-[0.8rem] font-extrabold text-gray-500">Total deductions</span>
                                    <strong class="text-[0.95rem] font-extrabold text-[#b22222]" x-text="money(slip.total_deductions)"></strong>
                                </div>
                            </section>
                        </div>

                        {{-- Net pay --}}
                        <div class="flex items-center justify-between rounded-xl border-t-2 border-[#b22222] bg-[#fef2f2] px-5 py-4 font-extrabold text-[#991b1b]">
                            <span>NET PAY</span>
                            <strong class="text-[1.55rem]" x-text="money(slip.net_pay)"></strong>
                        </div>
                    </div>
                </template>
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

                // [label, key in slip.deductions]
                deductionRows: [
                    ['MALASAKITAN', 'malasakitan'],
                    ['Late', 'late'],
                    ['Absences', 'absences'],
                    ['SSS', 'sss'],
                    ['SSS LOAN', 'sss_loan'],
                    ['PhilHealth', 'philhealth'],
                    ['PERAA/CEAP', 'peraa_ceap'],
                    ['PERAA/CEAP LOAN', 'peraa_ceap_loan'],
                    ['HDMF', 'hdmf'],
                    ['HDMF LOAN', 'hdmf_loan'],
                    ['Cash advance', 'cash_advance'],
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

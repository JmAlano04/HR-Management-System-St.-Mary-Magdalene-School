{{-- resources/views/payslips/employee.blade.php --}}

    {{-- Browser tab title --}}
    <x-slot name="title">
        Payslip
    </x-slot>

    <div
        class="mx-auto max-w-[1920px] px-4 pb-12 pt-6 sm:px-8 lg:px-12"
        x-data="employeePayslips(@js([
            'months'   => $months,
            'reopenId' => $reopenId,
        ]))"
        @keydown.escape.window="rejecting ? rejecting = false : slipId = null"
    >

        {{-- Page heading --}}
        <h1 class="mb-5 text-[clamp(2rem,3vw,3.2rem)] font-extrabold leading-[0.98] tracking-[-0.04em] text-[#b22222]">Payslip</h1>

        <section aria-labelledby="employeePayslipTitle"
            class="w-full rounded-[22px] border border-[#ecebea] bg-white p-[26px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

            {{-- Heading + status --}}
            <div class="mb-[22px] flex items-center justify-between gap-4">
                <div>
                    <p class="mb-2 text-[0.82rem] font-bold text-gray-500">Released payroll</p>
                    <h2 id="employeePayslipTitle" class="text-[1.45rem] font-bold text-gray-900">My Payslips</h2>
                </div>
                <span class="text-[0.82rem] font-bold text-gray-500">
                    {{ $total ? $total . ' released payslip' . ($total === 1 ? '' : 's') : 'No payslips released' }}
                </span>
            </div>

            {{-- Messages --}}
            @unless ($employee)
                <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    Your account is not linked to an employee record yet, so no payslips can be shown. Please contact HR.
                </div>
            @endunless
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-3">

                @if ($total === 0)
                    <div class="rounded-2xl border border-dashed border-[#d9dadd] px-5 py-[46px] text-center text-gray-500">
                        Your released payslips will appear here after Accounting sends them.
                    </div>
                @endif

                {{-- Month folders --}}
                <template x-if="!month">
                    <div class="grid gap-3">
                        <template x-for="m in months" :key="m.key">
                            <section class="overflow-hidden rounded-2xl border border-[#ecebea] bg-white">
                                <button type="button" @click="monthKey = m.key"
                                    class="group grid w-full cursor-pointer grid-cols-[42px_minmax(0,1fr)_auto] items-center gap-3.5 px-[18px] py-4 text-left transition hover:bg-[#fef2f2]">
                                    <span class="grid h-[42px] w-[42px] place-items-center rounded-xl bg-[#fef2f2] text-[1.1rem] text-[#b22222]">
                                        <i class="fa-solid fa-folder" aria-hidden="true"></i>
                                    </span>
                                    <span class="grid gap-1">
                                        <strong x-text="m.label"></strong>
                                        <small class="text-[0.78rem] text-gray-500" x-text="m.count + (m.count === 1 ? ' payslip' : ' payslips')"></small>
                                    </span>
                                    <i class="fa-solid fa-chevron-right text-gray-500 transition group-hover:translate-x-0.5" aria-hidden="true"></i>
                                </button>
                            </section>
                        </template>
                    </div>
                </template>

                {{-- Payslips inside one month --}}
                <template x-if="month">
                    <div class="grid gap-3">
                        <section class="rounded-2xl border border-[#ecebea] bg-white">
                            <div class="flex items-center gap-4 px-5 py-3">
                                <button type="button" @click="monthKey = null" aria-label="Back to months"
                                    class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-[10px] border border-[#ddd] bg-white text-slate-900 transition hover:border-[#b22222]/20 hover:bg-[#fff1f2] hover:text-[#b22222] hover:shadow-[0_12px_24px_rgba(178,34,34,0.12)]">
                                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                                </button>
                                <strong class="text-[clamp(1.5rem,2vw,2.1rem)] leading-[1.1]" x-text="month.label"></strong>
                            </div>

                            <div class="border-t border-[#ecebea] px-3 pb-3 pt-3">
                                <template x-for="r in month.records" :key="r.id">
                                    <button type="button" @click="slipId = r.id"
                                        class="grid w-full cursor-pointer grid-cols-[36px_minmax(0,1fr)_auto] items-center gap-3 border-b border-[#ecebea] px-2 py-3 text-left transition last:border-b-0 hover:bg-[#fef2f2]">
                                        <span class="grid h-9 w-9 place-items-center rounded-[9px] text-[1.1rem] text-[#b22222]">
                                            <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                                        </span>
                                        <span class="grid gap-1">
                                            <strong x-text="r.cutoff + ' Pay Period'"></strong>
                                            <small class="text-[0.78rem] text-gray-500" x-text="'Net pay ' + money(r.net_pay)"></small>
                                        </span>
                                        <span class="flex items-center gap-3">
                                            <span x-show="r.employee_status !== 'Pending'" class="rounded-full px-2.5 py-1 text-[0.7rem] font-extrabold"
                                                :class="r.employee_status === 'Approved' ? 'bg-[#e4f3e9] text-[#27853a]' : 'bg-[#fbe4e4] text-[#b22222]'"
                                                x-text="r.employee_status"></span>
                                            <i class="fa-solid fa-chevron-right text-gray-400" aria-hidden="true"></i>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </section>
                    </div>
                </template>
            </div>
        </section>

        {{-- ============ Payslip modal ============ --}}
        <div x-show="slip" x-cloak x-transition.opacity @click.self="slipId = null"
            class="fixed inset-0 z-[60] grid place-items-center bg-[rgba(17,24,39,0.48)] p-5">
            <section role="dialog" aria-modal="true" aria-labelledby="employeePayslipModalTitle"
                class="relative max-h-[calc(100vh-24px)] w-[min(900px,calc(100vw-32px))] overflow-auto rounded-[28px] bg-white px-3 pb-3 pt-[34px] shadow-[0_20px_50px_rgba(15,23,42,0.08)]">

                <button type="button" @click="slipId = null" aria-label="Close payslip"
                    class="absolute right-3 top-2.5 grid h-[34px] w-[34px] cursor-pointer place-items-center rounded-lg text-gray-400 transition hover:bg-[#fef2f2] hover:text-[#b22222]">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

                <template x-if="slip">
                    <div class="bg-white">
                        <header class="-mt-[15px] grid px-4 py-[5px] text-center">
                            <strong id="employeePayslipModalTitle" class="text-2xl" x-text="slip.employee_name"></strong>
                            <span class="text-gray-500" x-text="'ID: ' + slip.employee_number"></span>
                            <small class="text-gray-500" x-text="slip.department + ' | ' + slip.position + ' | ' + slip.step_name"></small>
                        </header>

                        {{-- Pay period + attendance --}}
                        <div class="grid grid-cols-2 border-y border-[#ecebea] sm:grid-cols-4">
                            <div class="grid border-r border-[#ecebea] px-4 py-[5px] text-center"><span class="text-[0.6rem] font-extrabold uppercase text-gray-500">Pay Period</span><strong x-text="slip.cutoff"></strong></div>
                            <div class="grid border-[#ecebea] px-4 py-[5px] text-center sm:border-r"><span class="text-[0.6rem] font-extrabold uppercase text-gray-500">Days worked</span><strong x-text="Number(slip.days_worked)"></strong></div>
                            <div class="grid border-r border-t border-[#ecebea] px-4 py-[5px] text-center sm:border-t-0"><span class="text-[0.6rem] font-extrabold uppercase text-gray-500">Hours worked</span><strong x-text="Number(slip.hours_worked)"></strong></div>
                            <div class="grid border-t border-[#ecebea] px-4 py-[5px] text-center sm:border-t-0"><span class="text-[0.6rem] font-extrabold uppercase text-gray-500">Late (mins)</span><strong x-text="slip.late_minutes"></strong></div>
                        </div>

                        {{-- Earnings + deductions --}}
                        <div class="grid sm:grid-cols-2">
                            <section class="p-4">
                                <h3 class="mb-2 text-[0.6rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Earnings</h3>
                                <template x-for="row in earningRows" :key="row[0]">
                                    <div class="flex min-h-[32px] justify-between gap-3 border-b border-[#ecebea] py-1.5 text-[0.82rem] text-gray-500">
                                        <span x-text="row[0]"></span>
                                        <strong class="whitespace-nowrap text-right text-gray-900" x-text="money(row[1])"></strong>
                                    </div>
                                </template>
                                <div class="mt-1.5 flex min-h-[32px] justify-between gap-3 py-1.5 text-[0.82rem] font-extrabold text-gray-900">
                                    <span>Gross earnings</span>
                                    <strong class="whitespace-nowrap text-right text-[#b22222]" x-text="money(slip.gross_pay)"></strong>
                                </div>
                            </section>

                            <section class="border-t border-[#ecebea] p-4 sm:border-l sm:border-t-0">
                                <h3 class="mb-2 text-[0.6rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Deductions</h3>
                                <template x-for="row in deductionRows" :key="row[0]">
                                    <div class="flex min-h-[32px] justify-between gap-3 border-b border-[#ecebea] py-1.5 text-[0.82rem] text-gray-500">
                                        <span x-text="row[0]"></span>
                                        <strong class="whitespace-nowrap text-right text-gray-900" x-text="money(slip.deductions[row[1]] ?? 0)"></strong>
                                    </div>
                                </template>
                                <div class="mt-1.5 flex min-h-[32px] justify-between gap-3 py-1.5 text-[0.82rem] font-extrabold text-gray-900">
                                    <span>Total deductions</span>
                                    <strong class="whitespace-nowrap text-right text-[#b22222]" x-text="money(slip.total_deductions)"></strong>
                                </div>
                            </section>
                        </div>

                        {{-- Net pay --}}
                        <div class="flex justify-between border-t-2 border-[#b22222] bg-[#fef2f2] px-[18px] py-2.5 font-black tracking-[0.08em] text-[#b22222]">
                            <span>NET PAY</span>
                            <strong class="tracking-normal" x-text="money(slip.net_pay)"></strong>
                        </div>

                        {{-- Decision --}}
                        <template x-if="slip.employee_status === 'Rejected'">
                            <div class="flex gap-2.5 px-[18px] pb-1.5 pt-3.5 font-extrabold text-[#b22222]" role="status"
                                x-text="'Payslip rejected' + (slip.employee_reason ? ': ' + slip.employee_reason : '')"></div>
                        </template>

                        <template x-if="slip.employee_status === 'Approved'">
                            <div class="flex gap-2.5 px-[18px] pb-1.5 pt-3.5 font-extrabold text-[#27853a]" role="status">Payslip approved</div>
                        </template>

                        <template x-if="slip.employee_status === 'Pending'">
                            <div class="flex justify-between gap-2.5 px-[18px] pb-1.5 pt-3.5">
                                <form method="POST" action="{{ route('payslips.decide') }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="payslip_id" :value="slipId">
                                    <input type="hidden" name="decision" value="approve">
                                    <button type="submit"
                                        class="min-h-[40px] cursor-pointer rounded-[9px] bg-[#2f9e44] px-[18px] py-[9px] font-extrabold text-white transition hover:bg-[#27853a]">Approve</button>
                                </form>
                                <button type="button" @click="rejecting = true; reason = ''"
                                    class="min-h-[40px] cursor-pointer rounded-[9px] border border-[#b22222] bg-[#b22222] px-[18px] py-[9px] font-extrabold text-white transition hover:bg-[#8d1111]">Reject</button>
                            </div>
                        </template>
                    </div>
                </template>
            </section>
        </div>

        {{-- ============ Rejection dialog ============ --}}
        <div x-show="rejecting && slip" x-cloak x-transition.opacity @click.self="rejecting = false"
            class="fixed inset-0 z-[70] grid place-items-center bg-[rgba(17,24,39,0.58)] p-5">
            <section role="dialog" aria-modal="true" aria-labelledby="employeeRejectionTitle"
                class="relative w-[min(440px,calc(100vw-32px))] rounded-[22px] bg-white p-7 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">

                <button type="button" @click="rejecting = false" aria-label="Close rejection dialog"
                    class="absolute right-3 top-2.5 grid h-[34px] w-[34px] cursor-pointer place-items-center rounded-lg text-gray-400 transition hover:bg-[#fef2f2] hover:text-[#b22222]">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

                <h2 id="employeeRejectionTitle" class="mb-2 mr-9 text-[1.35rem] font-bold text-gray-900">Reject payslip</h2>
                <p class="mb-5 text-gray-500">Please provide a reason for rejecting this payslip.</p>

                <form method="POST" action="{{ route('payslips.decide') }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="payslip_id" :value="slipId">
                    <input type="hidden" name="decision" value="reject">

                    <label for="employeeRejectionReason" class="mb-2 block font-bold">Reason</label>
                    <textarea id="employeeRejectionReason" name="reason" rows="4" required maxlength="1000" x-model="reason" x-ref="reasonBox"
                        placeholder="Explain why you are rejecting this payslip"
                        class="min-h-[110px] w-full resize-y rounded-[10px] border border-gray-300 px-3 py-[11px] text-gray-900 focus:border-[#b22222] focus:outline-none focus:ring-[3px] focus:ring-[#b22222]/15"></textarea>

                    <div class="mt-4 flex justify-end gap-2.5">
                        <button type="button" @click="rejecting = false"
                            class="min-h-[40px] cursor-pointer rounded-[9px] border border-[#b22222] bg-white px-[18px] py-[9px] font-extrabold text-[#b22222] transition hover:bg-red-50">Cancel</button>
                        <button type="submit"
                            class="min-h-[40px] cursor-pointer rounded-[9px] border border-[#b22222] bg-[#b22222] px-[18px] py-[9px] font-extrabold text-white transition hover:bg-[#8d1111]">Submit rejection</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        function employeePayslips(cfg) {
            return {
                months: cfg.months,
                monthKey: null,   // which month folder is open
                slipId: null,     // which payslip is shown in the modal
                rejecting: false,
                reason: '',

                get month() {
                    return this.months.find((m) => m.key === this.monthKey) || null;
                },

                get slip() {
                    if (!this.slipId) return null;
                    for (const m of this.months) {
                        const found = m.records.find((r) => r.id === this.slipId);
                        if (found) return found;
                    }
                    return null;
                },

                get earningRows() {
                    const s = this.slip;
                    return s ? [
                        ['Basic salary', s.basic_pay],
                        ['Daily rate', s.daily_wage],
                        ['Honorarium', s.honorarium],
                    ] : [];
                },

                // [label, key in slip.deductions]
                deductionRows: [
                    ['MALASAKITAN', 'malasakitan'],
                    ['Late', 'late'],
                    ['Absences', 'absences'],
                    ['SSS', 'sss'],
                    ['SSS LOAN', 'sss_loan'],
                    ['PhilHealth', 'philhealth'],
                    ['PhilHealth LOAN', 'philhealth_loan'],
                    ['PERAA/CEAP', 'peraa_ceap'],
                    ['PERAA/CEAP LOAN', 'peraa_ceap_loan'],
                    ['HDMF', 'hdmf'],
                    ['HDMF LOAN', 'hdmf_loan'],
                    ['Cash advance', 'cash_advance'],
                ],

                init() {
                    // After approving / rejecting the page reloads: reopen the same payslip so the decision shows
                    if (cfg.reopenId) {
                        for (const m of this.months) {
                            if (m.records.some((r) => r.id === cfg.reopenId)) {
                                this.monthKey = m.key;
                                this.slipId = cfg.reopenId;
                                break;
                            }
                        }
                    }
                },

                money(value) {
                    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0);
                },
            };
        }
    </script>

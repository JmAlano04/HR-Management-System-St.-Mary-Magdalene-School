{{-- resources/views/salary-records/index.blade.php --}}


    {{-- Browser tab title --}}
    <x-slot name="title">
       Accounting Salary Record Management
    </x-slot>

    {{-- Page heading (shown in the layout's top bar) --}}
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none  ml-20 tracking-tight text-[#b22222] sm:text-4xl">Salary Record Management</h1>
    </x-slot>

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="salaryRecords(@js(['months' => $months]))"
        @keydown.escape.window="slipId = null"
    >
        <section aria-labelledby="historyTitle"
            class="mt-2 rounded-[22px] border border-[#ecebea] bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

            {{-- Heading + cutoff tabs --}}
            <div class="mb-4 flex flex-col items-start justify-between gap-[18px] sm:flex-row sm:items-center">
                <div>
                    <p class="mb-1.5 text-[0.72rem] font-extrabold uppercase leading-tight tracking-[0.08em] text-[#b22222]">Employee distribution</p>
                    <h2 id="historyTitle" class="text-[2rem] font-bold text-gray-900">History</h2>
                </div>

                <nav class="hrms-tablist" aria-label="Salary cutoff period">
                    @foreach (['15th', '30th'] as $tab)
                        <a href="{{ route('salary-records.index', ['cutoff' => $tab]) }}"
                            aria-selected="{{ $cutoff === $tab ? 'true' : 'false' }}"
                            class="hrms-tab text-base font-extrabold">
                            {{ $tab }}
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="flex flex-col gap-3" aria-live="polite">

                {{-- Nothing transferred yet --}}
                @if (count($months) === 0)
                    <div class="grid min-h-[140px] place-items-center rounded-[14px] border border-[#ecebea] bg-white font-bold text-gray-500">
                        No payslips transferred yet.
                    </div>
                @endif

                {{-- Month folders --}}
                <template x-if="!month">
                    <div class="flex flex-col gap-3">
                        <template x-for="m in months" :key="m.key">
                            <button type="button" @click="monthKey = m.key" :aria-expanded="false"
                                class="grid w-full cursor-pointer grid-cols-[42px_minmax(0,1fr)_auto] items-center gap-3.5 rounded-[14px] border border-[#ecebea] bg-white px-5 py-[18px] text-left transition duration-200 hover:border-[#b22222]/30 hover:bg-[#fff8f8]">
                                <span class="grid h-[42px] w-[42px] place-items-center rounded-xl bg-[#fef2f2] text-[1.1rem] text-[#b22222]">
                                    <i class="fa-solid fa-folder" aria-hidden="true"></i>
                                </span>
                                <span class="grid gap-1">
                                    <strong class="text-[1.1rem] text-gray-900" x-text="m.label"></strong>
                                    <small class="text-[0.78rem] text-gray-500" x-text="m.count + (m.count === 1 ? ' record' : ' records')"></small>
                                </span>
                                <i class="fa-solid fa-chevron-right text-gray-500" aria-hidden="true"></i>
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Employees inside one month --}}
                <template x-if="month">
                    <div class="grid gap-3">
                        <button type="button" @click="monthKey = null" aria-label="Back to months"
                            class="inline-flex h-[42px] w-[42px] cursor-pointer items-center justify-center rounded-[10px] border border-[#ecebea] bg-white text-gray-900 transition hover:border-[#b22222]/30 hover:bg-[#fff8f8]">
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        </button>

                        <div class="overflow-hidden rounded-[14px] border border-[#ecebea] bg-white">
                            <div class="flex items-center justify-between gap-3 border-b border-[#ecebea] px-5 pb-3.5 pt-[18px]">
                                <strong class="text-[clamp(1.6rem,2vw,2.2rem)] tracking-tight text-gray-900" x-text="month.label"></strong>
                                <span class="text-[0.8rem] font-bold text-gray-500" x-text="month.count + (month.count === 1 ? ' record' : ' records')"></span>
                            </div>

                            <div class="flex flex-col">
                                <template x-for="r in month.records" :key="r.id">
                                    <button type="button" @click="slipId = r.id"
                                        class="flex w-full cursor-pointer items-center justify-between border-0 border-b border-[#ecebea] bg-transparent px-5 py-[18px] text-left text-[1.05rem] font-bold text-[#b22222] transition last:border-b-0 hover:bg-[#fff8f8]">
                                        <span x-text="r.employee_name"></span>
                                        <i class="fa-solid fa-chevron-right text-gray-500" aria-hidden="true"></i>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        {{-- ============ Payslip modal ============ --}}
        <div x-show="slip" x-cloak x-transition.opacity @click.self="slipId = null"
            class="fixed inset-0 z-[60] grid place-items-center bg-[rgba(17,24,39,0.48)] p-5">
            <div role="dialog" aria-modal="true" aria-labelledby="historyModalTitle"
                class="relative w-[min(820px,calc(100vw-40px))] rounded-[24px] bg-white px-3 pb-3 pt-2 shadow-[0_18px_40px_rgba(15,23,42,0.08)]">

                <button type="button" @click="slipId = null" aria-label="Close employee details"
                    class="absolute right-3 top-3 grid h-8 w-8 cursor-pointer place-items-center rounded-full text-xl text-gray-500 transition hover:bg-[#fef2f2] hover:text-[#b22222]">
                    &times;
                </button>

                <template x-if="slip">
                    <div class="bg-white">
                        <div class="flex flex-col items-center gap-0.5 px-4 pb-4 pt-3 text-center">
                            <h3 id="historyModalTitle" class="text-[clamp(1.5rem,2vw,2.25rem)] font-extrabold leading-tight text-gray-900" x-text="slip.employee_name"></h3>
                            <span class="text-[0.82rem] text-gray-800" x-text="'ID: ' + slip.employee_number"></span>
                            <span class="text-[0.82rem] text-gray-500" x-text="slip.department + ' | ' + slip.position + ' | ' + slip.step_name"></span>
                        </div>

                        <div class="grid grid-cols-2 border-y border-[#ecebea] sm:grid-cols-5">
                            <template x-for="(m, k) in [
                                ['Date', slip.cutoff_label || slip.cutoff],
                                ['Days worked', Number(slip.days_worked)],
                                ['Hours worked', Number(slip.hours_worked)],
                                ['Late (mins)', Number(slip.late_minutes)],
                                ['Monthly wage', money((Number(slip.daily_wage) || 0) * 26)]
                            ]" :key="k">
                                <div class="grid justify-items-center gap-[3px] px-1.5 py-2.5 text-center" :class="k > 0 ? 'sm:border-l sm:border-[#ecebea]' : ''">
                                    <span class="text-[0.68rem] font-extrabold uppercase tracking-[0.08em] text-gray-500" x-text="m[0]"></span>
                                    <strong class="text-[0.95rem] text-gray-900" x-text="m[1]"></strong>
                                </div>
                            </template>
                        </div>

                        <div class="grid sm:grid-cols-2">
                            <section class="min-w-0 border-t border-[#ecebea] p-3">
                                <h4 class="mb-2.5 text-[0.78rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Earnings</h4>
                                <div class="flex min-h-[30px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.85rem] text-gray-500">
                                    <span>Basic salary</span>
                                    <strong class="min-w-[120px] text-center text-gray-900" x-text="money(slip.basic_pay)"></strong>
                                </div>
                                <div class="flex min-h-[30px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.85rem] text-gray-500">
                                    <span>Daily rate</span>
                                    <strong class="min-w-[120px] text-center text-gray-900" x-text="money(slip.daily_wage)"></strong>
                                </div>
                                <div class="flex min-h-[30px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.85rem] text-gray-500">
                                    <span>Honorarium</span>
                                    <strong class="min-w-[120px] text-center text-gray-900" x-text="Number(slip.honorarium) > 0 ? money(slip.honorarium) : '-'"></strong>
                                </div>
                                <div class="mt-3 flex min-h-[30px] items-center justify-between gap-3 text-[0.85rem] font-extrabold text-gray-900">
                                    <span>Gross earnings</span>
                                    <strong class="min-w-[120px] text-center text-[#b22222]" x-text="money(slip.gross_pay)"></strong>
                                </div>
                            </section>

                            <section class="min-w-0 border-t border-[#ecebea] p-3 sm:border-l">
                                <h4 class="mb-2.5 text-[0.78rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Deductions</h4>
                                <template x-for="(row, i) in deductionRows" :key="row[0]">
                                    <div class="flex min-h-[30px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.85rem] text-gray-500">
                                        <span x-text="row[0]"></span>
                                        <strong class="min-w-[120px] text-center text-gray-900" x-text="money(row[1])"></strong>
                                    </div>
                                </template>
                                <div class="mt-3 flex min-h-[30px] items-center justify-between gap-3 text-[0.85rem] font-extrabold text-gray-900">
                                    <span>Total deductions</span>
                                    <strong class="min-w-[120px] text-center text-[#b22222]" x-text="'- ' + money(slip.total_deductions)"></strong>
                                </div>
                            </section>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t-2 border-[#b22222] bg-[#fef2f2] px-5 py-3 font-black tracking-[0.08em] text-[#b22222]">
                            <span>NET PAY</span>
                            <strong class="text-lg tracking-normal" x-text="money(slip.net_pay)"></strong>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function salaryRecords(cfg) {
            return {
                months: cfg.months,
                monthKey: null,   // which month folder is open
                slipId: null,     // which payslip is shown in the modal

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
                        ['Gross earnings', s.gross_pay],
                    ] : [];
                },

                get deductionRows() {
                    const s = this.slip;
                    if (!s) return [];
                    const d = s.deductions;
                    return [
                        ['MALASAKITAN', d.malasakitan],
                        ['Late', d.late],
                        ['SSS', d.sss],
                        ['SSS LOAN', d.sss_loan],
                        ['PhilHealth', d.philhealth],
                        ['PERAA/CEAP', d.peraa_ceap],
                        ['PERAA/CEAP LOAN', d.peraa_ceap_loan],
                        ['HDMF', d.hdmf],
                        ['HDMF LOAN', d.hdmf_loan],
                        ['Cash advance', d.cash_advance],
                        ['Total deductions', s.total_deductions],
                    ];
                },

                money(value) {
                    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0);
                },
            };
        }
    </script>

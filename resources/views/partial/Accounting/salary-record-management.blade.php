{{-- resources/views/salary-records/index.blade.php --}}


    {{-- Browser tab title --}}
    <x-slot name="title">
        Salary Record Management
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

                <nav class="flex items-center gap-2" aria-label="Salary cutoff period">
                    @foreach (['15th', '30th'] as $tab)
                        <a href="{{ route('salary-records.index', ['cutoff' => $tab]) }}"
                            aria-selected="{{ $cutoff === $tab ? 'true' : 'false' }}"
                            class="inline-flex min-h-[42px] items-center justify-center rounded-[10px] border px-[22px] text-base font-extrabold transition duration-200
                                {{ $cutoff === $tab
                                    ? 'border-[#b22222] bg-[#b22222] text-white shadow-[0_10px_18px_rgba(178,34,34,0.18)] -translate-y-px'
                                    : 'border-transparent text-gray-700 hover:-translate-y-px hover:border-[#d93c3c] hover:bg-[#d93c3c] hover:text-white' }}">
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
                class="relative max-h-[calc(100vh-48px)] w-[min(900px,calc(100vw-40px))] overflow-y-auto [scrollbar-width:none] rounded-[18px] bg-white pt-[5px] shadow-[0_18px_40px_rgba(15,23,42,0.08)]">

                <button type="button" @click="slipId = null" aria-label="Close employee details"
                    class="absolute right-4 top-3 grid h-[34px] w-[34px] cursor-pointer place-items-center rounded-[10px] text-[1.3rem] text-[#7a7a7a] transition hover:bg-[#b22222]/10 hover:text-[#b22222]">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

                <template x-if="slip">
                    <div class="bg-white px-[18px] pt-[18px]">

                        {{-- Header --}}
                        <div class="px-2 pb-4 pt-2 text-center">
                            <h3 id="historyModalTitle" class="text-[clamp(2rem,2.6vw,2.6rem)] font-extrabold leading-[1.08] tracking-[-0.05em] text-[#2f2e2e]" x-text="slip.employee_name"></h3>
                            <div class="mt-2.5 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-[0.86rem] font-semibold text-[#4a4a4a]">
                                <span x-text="'ID: ' + slip.employee_number"></span>
                                <span class="before:mr-3 before:text-[#7f7f7f] before:content-['|']" x-text="slip.department + ' | ' + slip.position + ' | ' + slip.step_name"></span>
                            </div>
                        </div>

                        {{-- Attendance summary --}}
                        <div class="grid grid-cols-2 border-y border-[#d1d1d1] sm:grid-cols-4">
                            <div class="grid gap-2 border-r border-[#d1d1d1] py-2 text-center">
                                <span class="text-[0.7rem] font-black uppercase tracking-[0.1em] text-[#5a5a5a]">Days worked</span>
                                <strong class="text-[clamp(1rem,1.3vw,1.55rem)] font-bold tracking-tight text-[#1f1f1f]" x-text="Number(slip.days_worked)"></strong>
                            </div>
                            <div class="grid gap-2 border-r-0 border-[#d1d1d1] py-2 text-center sm:border-r">
                                <span class="text-[0.7rem] font-black uppercase tracking-[0.1em] text-[#5a5a5a]">Hours worked</span>
                                <strong class="text-[clamp(1rem,1.3vw,1.55rem)] font-bold tracking-tight text-[#1f1f1f]" x-text="Number(slip.hours_worked)"></strong>
                            </div>
                            <div class="grid gap-2 border-r border-t border-[#d1d1d1] py-2 text-center sm:border-t-0">
                                <span class="text-[0.7rem] font-black uppercase tracking-[0.1em] text-[#5a5a5a]">Late (mins)</span>
                                <strong class="text-[clamp(1rem,1.3vw,1.55rem)] font-bold tracking-tight text-[#1f1f1f]" x-text="slip.late_minutes"></strong>
                            </div>
                            <div class="grid gap-2 border-t border-[#d1d1d1] py-2 text-center sm:border-t-0">
                                <span class="text-[0.7rem] font-black uppercase tracking-[0.1em] text-[#5a5a5a]">Cutoff</span>
                                <strong class="text-[clamp(1rem,1.3vw,1.55rem)] font-bold tracking-tight text-[#1f1f1f]" x-text="slip.cutoff"></strong>
                            </div>
                        </div>

                        {{-- Earnings + deductions --}}
                        <div class="grid border-b border-[#d1d1d1] sm:grid-cols-2">
                            <section class="px-2.5 py-1.5">
                                <h4 class="mb-2.5 text-[0.7rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Earnings</h4>
                                <div class="grid">
                                    <template x-for="(row, i) in earningRows" :key="row[0]">
                                        <div class="grid min-h-[30px] grid-cols-[1fr_auto] items-center gap-3 border-t border-[#d1d1d1] px-1.5 py-[5px] text-[0.8rem] first:border-t-0">
                                            <span class="font-medium text-[#3d3d3d]" x-text="row[0]"></span>
                                            <strong class="text-[0.9rem]" :class="i === earningRows.length - 1 ? 'font-black text-[#b22222]' : 'font-bold text-[#1b1b1b]'" x-text="money(row[1])"></strong>
                                        </div>
                                    </template>
                                </div>
                            </section>

                            <section class="border-t border-[#d1d1d1] px-2.5 py-1.5 sm:border-l sm:border-t-0">
                                <h4 class="mb-2.5 text-[0.7rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Deductions</h4>
                                <div class="grid">
                                    <template x-for="(row, i) in deductionRows" :key="row[0]">
                                        <div class="grid min-h-[30px] grid-cols-[1fr_auto] items-center gap-3 border-t border-[#d1d1d1] px-1.5 py-[5px] text-[0.8rem] first:border-t-0">
                                            <span class="font-medium text-[#3d3d3d]" x-text="row[0]"></span>
                                            <strong class="text-[0.9rem]" :class="i === deductionRows.length - 1 ? 'font-black text-[#b22222]' : 'font-bold text-[#1b1b1b]'" x-text="money(row[1])"></strong>
                                        </div>
                                    </template>
                                </div>
                            </section>
                        </div>

                        {{-- Net pay --}}
                        <div class="-mx-[18px] flex items-center justify-between gap-[18px] border-t-[3px] border-[#b22222] bg-[#b22222]/[0.07] px-[18px] py-3.5 font-black uppercase tracking-[0.08em] text-[#b22222]">
                            <span class="text-[0.7rem]">Net Pay</span>
                            <strong class="text-[clamp(1.1rem,1.5vw,1.8rem)] normal-case tracking-tight" x-text="money(slip.net_pay)"></strong>
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
                        ['Absences', d.absences],
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

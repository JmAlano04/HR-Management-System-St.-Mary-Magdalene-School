{{-- resources/views/payroll-processing/index.blade.php --}}

    <x-slot name="title">
        Payroll Processing
    </x-slot>

    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none ml-[80px] tracking-tight text-[#b22222] sm:text-4xl">Payroll Processing</h1>
    </x-slot>

    @php
        $btn   = 'inline-flex min-h-[42px] cursor-pointer items-center justify-center gap-2 rounded-xl border border-[#f1caca] bg-[#b22222] px-4 font-bold text-white transition hover:-translate-y-px hover:bg-[#d93c3c] hover:shadow-[0_10px_18px_rgba(178,34,34,0.18)]';
        $input = 'w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm font-bold text-gray-900 focus:border-[#e7b0b0] focus:outline-none focus:ring-[3px] focus:ring-red-700/10';
        $row   = 'flex min-h-[30px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.85rem] text-gray-500';
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="payrollProcessing(@js([
            'employees'   => $employees,
            'steps'       => $steps,
            'stepsUrl'    => route('payroll-accounting.steps'),
            'saveUrl'     => route('payroll-accounting.save'),
            'pageUrl'     => route('payroll-accounting.index'),
            'period'      => $period,
            'cutoff'      => $cutoff,
            'csrf'        => csrf_token(),
        ]))"
        @keydown.escape.window="closeAll()"
    >

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Toolbar --}}
        <section class="mb-4 flex flex-wrap items-end gap-2.5" aria-label="Payroll controls">
            <label class="grid gap-1 text-[0.82rem] font-bold text-gray-500">
                <input type="month" x-model="period" @change="period && go()"
                    class="min-h-[42px] rounded-xl border border-[#ecebea] bg-white px-3 text-gray-900 hover:border-[#efb0b0]">
            </label>
            <button type="button" class="{{ $btn }}" @click="openSteps()">
                <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit steps
            </button>
            <span class="pb-3 text-[0.8rem] font-semibold"
                :class="saveState === 'error' ? 'text-red-600' : 'text-gray-500'" x-text="saveLabel" aria-live="polite"></span>
        </section>

        {{-- Payroll table --}}
        <section aria-labelledby="payrollTableTitle"
            class="overflow-hidden rounded-[22px] border border-[#ecebea] bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
            <div class="flex flex-col items-start justify-between gap-4 px-6 py-[22px] sm:flex-row sm:items-center">
                <div>
                    <p class="mb-1.5 text-[0.72rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Employee register</p>
                    <h2 id="payrollTableTitle" class="text-[1.35rem] font-bold text-gray-900">Payroll Calculation</h2>
                </div>
                <nav class="flex items-center gap-2" aria-label="Payroll pay period">
                    <template x-for="c in [['15th', '15th Pay Period'], ['30th', '30th Pay Period']]" :key="c[0]">
                        <button type="button" @click="setCutoff(c[0])"
                            class="min-h-[38px] cursor-pointer rounded-[10px] border px-[18px] text-[0.88rem] font-extrabold transition"
                            :class="cutoff === c[0]
                                ? 'border-[#b22222] bg-[#b22222] text-white shadow-[0_10px_18px_rgba(178,34,34,0.18)]'
                                : 'border-transparent bg-transparent text-gray-700 hover:bg-[#d93c3c] hover:text-white'"
                            :aria-selected="cutoff === c[0]" x-text="c[1]"></button>
                    </template>
                </nav>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-[1100px] table-fixed border-collapse">
                    <thead>
                        <tr class="bg-[#f9fafb] text-[0.72rem] uppercase tracking-[0.06em] text-gray-500">
                            <th class="w-[4%] px-2 py-5 text-center">
                                <input type="checkbox" aria-label="Select all employees"
                                    class="h-4 w-4 cursor-pointer rounded border-gray-300 accent-[#b22222]"
                                    :checked="allSelected" @change="toggleAll($event.target.checked)">
                            </th>
                            <th class="w-[16%] px-4 py-5 text-center">Employee</th>
                            <th class="w-[15%] px-4 py-5 text-center">Employee Type</th>
                            <th class="w-[8%] px-4 py-5 text-center">Days worked</th>
                            <th class="w-[8%] px-4 py-5 text-center">Hours worked</th>
                            <th class="w-[8%] px-4 py-5 text-center">Late (mins)</th>
                            <th class="w-[13%] px-4 py-5 text-center">Basic pay</th>
                            <th class="w-[14%] px-4 py-5 text-center">Deductions</th>
                            <th class="w-[14%] px-4 py-5 text-center">Net pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(e, i) in employees" :key="e.id">
                            <tr class="border-t border-[#ecebea] text-center text-[0.82rem]"
                                :class="selected.includes(e.id) ? 'bg-[#fef2f2]' : ''">
                                <td class="px-2 py-4">
                                    <input type="checkbox" :aria-label="'Select ' + e.name"
                                        class="h-4 w-4 cursor-pointer rounded border-gray-300 accent-[#b22222]"
                                        :checked="selected.includes(e.id)" @change="toggleOne(e.id, $event.target.checked)">
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col items-center gap-0.5">
                                        <button type="button" @click="openDetails(i)"
                                            class="cursor-pointer rounded-lg px-2 py-1 font-extrabold text-[#b22222] hover:bg-[#fef2f2] hover:underline" x-text="e.name"></button>
                                        <span class="text-[0.72rem] font-semibold text-gray-500" x-text="e.employeeNumber"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-gray-600" x-text="e.position || e.department || '-'"></td>
                                <td class="px-4 py-4"><input type="number" min="0" max="31" step="1" class="{{ $input }}" :value="e.daysWorked" @input="setDays(e, $event.target.value, $event.target)"></td>
                                <td class="px-4 py-4"><input type="number" min="0" max="248" step="1" class="{{ $input }}" :value="e.hoursWorked" @input="setHours(e, $event.target.value, $event.target)"></td>
                                <td class="px-4 py-4"><input type="number" min="0" step="1" class="{{ $input }}" :value="e.lateMinutes" @input="setLate(e, $event.target.value, $event.target)"></td>
                                <td class="whitespace-nowrap px-4 py-4 font-bold" x-text="money(calc(e).basicPay)"></td>
                                <td class="whitespace-nowrap px-4 py-4 font-bold" x-text="money(calc(e).totalDeductions)"></td>
                                <td class="whitespace-nowrap px-4 py-4 font-bold text-[#b22222]" x-text="money(calc(e).netPay)"></td>
                            </tr>
                        </template>
                        <tr x-show="employees.length === 0">
                            <td colspan="9" class="border-t border-[#ecebea] px-4 py-8 text-center font-semibold text-gray-500">No employees found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Transfer --}}
        <form method="POST" action="{{ route('payroll-accounting.transfer') }}"
            class="sticky bottom-[18px] z-20 mt-[18px] flex justify-end"
            @submit.prevent="transfer($event)">
            @csrf
            <input type="hidden" name="cutoff" :value="cutoff">
            <input type="hidden" name="period" :value="period">
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="employee_ids[]" :value="id">
            </template>
            <button type="submit" :disabled="selected.length === 0"
                class="inline-flex min-h-[44px] cursor-pointer items-center justify-center gap-2.5 rounded-xl border border-[#f1caca] bg-[#b22222] px-[18px] font-extrabold text-white transition hover:-translate-y-px hover:bg-[#d93c3c] disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:bg-[#b22222]">
                <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                <span x-text="selected.length ? 'Transfer ' + selected.length + ' selected to Salary Monitoring' : 'Select employees to transfer'"></span>
            </button>
        </form>

        {{-- ============ Edit steps modal ============ --}}
        <div x-show="stepsOpen" x-cloak x-transition.opacity @click.self="stepsOpen = false"
            class="fixed inset-0 z-[1200] grid place-items-center bg-[rgba(17,24,39,0.48)] p-3">
            <section role="dialog" aria-modal="true"
                class="max-h-[calc(100vh-24px)] w-[min(600px,calc(100vw-32px))] overflow-y-auto rounded-[22px] bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
                <div class="flex justify-between gap-4">
                    <div>
                        <p class="mb-1.5 text-[0.72rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Payroll workflow</p>
                        <h2 class="text-[1.35rem] font-bold text-gray-900">Edit Payroll steps</h2>
                    </div>
                    <button type="button" @click="stepsOpen = false" aria-label="Close"
                        class="h-9 w-9 cursor-pointer rounded-[10px] text-lg text-gray-400 hover:bg-[#fef2f2] hover:text-[#b22222]">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <p class="mt-2 text-[0.9rem] leading-snug text-gray-500">Set the daily wage for each payroll step. Employee basic pay is prorated by the number of hours worked, and the full daily wage is only earned at 8 hours.</p>

                <div class="my-4 grid gap-[7px]">
                    <template x-for="(s, i) in stepsDraft" :key="i">
                        <div class="flex items-center gap-[9px]">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#fef2f2] text-[0.8rem] font-extrabold text-[#b22222]" x-text="i + 1"></span>
                            <input type="text" x-model="s.name" :aria-label="'Payroll step ' + (i + 1) + ' name'"
                                class="min-h-[36px] w-full rounded-[9px] border border-[#ecebea] px-[11px]">
                            <label class="grid min-w-[125px] gap-1 text-[0.7rem] font-bold text-gray-500">
                                <span>Daily wage</span>
                                <input type="number" min="0" step="0.01" x-model.number="s.dailyWage"
                                    class="min-h-[34px] rounded-[9px] border border-[#ecebea] bg-[#f9fafb] px-[11px]">
                            </label>
                        </div>
                    </template>
                </div>

                <p x-show="stepsError" x-text="stepsError" class="mb-2 text-xs font-semibold text-red-600"></p>
                <div class="flex justify-end gap-2.5">
                    <button type="button" class="{{ $btn }}" @click="stepsOpen = false">Cancel</button>
                    <button type="button" class="{{ $btn }}" @click="saveSteps()" :disabled="savingSteps">Save steps</button>
                </div>
            </section>
        </div>

        {{-- ============ Payslip details modal ============ --}}
        <div x-show="draft" x-cloak x-transition.opacity @click.self="closeAll()"
            class="fixed inset-0 z-[1200] grid place-items-center bg-[rgba(17,24,39,0.48)] p-3">
            <section role="dialog" aria-modal="true"
               class="max-h-[calc(100vh-40px)] w-[min(900px,calc(100vw-40px))] overflow-y-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden rounded-[28px] bg-white px-3.5 pb-3.5 pt-2 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
                <div class="mb-2 flex justify-end">
                    <button type="button" @click="closeAll()" aria-label="Close"
                        class="h-9 w-9 cursor-pointer rounded-[10px] text-lg text-gray-400 hover:bg-[#fef2f2] hover:text-[#b22222]">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <template x-if="draft">
                    <div class="bg-white">
                        <div class="flex flex-col items-center gap-0.5 px-[18px] pb-[18px] pt-2.5 text-center">
                            <strong class="text-[clamp(1.5rem,2vw,2.25rem)] font-extrabold leading-tight text-gray-900" x-text="draft.name"></strong>
                            <span class="text-[0.88rem] text-gray-800" x-text="'ID: ' + draft.employeeNumber"></span>
                            <span class="text-[0.88rem] text-gray-500" x-text="draft.department + ' | ' + draft.position + ' | ' + preview.step.name"></span>
                            <select x-model.number="draft.step" aria-label="Payroll step"
                                class="mt-1 min-h-[28px] w-[110px] rounded-lg border border-[#ecebea] bg-white px-1.5 text-[0.82rem]">
                                <template x-for="(s, i) in steps" :key="i">
                                    <option :value="i + 1" x-text="s.name" :selected="draft.step === i + 1"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 border-y border-[#ecebea] sm:grid-cols-5">
                            <template x-for="(m, k) in [
                                ['Pay period', cutoff],
                                ['Days worked', draft.daysWorked],
                                ['Hours worked', draft.hoursWorked],
                                ['Late (mins)', draft.lateMinutes],
                                ['Monthly wage (daily x 26)', money(preview.monthlyWage)]
                            ]" :key="k">
                                <div class="grid justify-items-center gap-[3px] px-1.5 py-2.5 text-center" :class="k > 0 ? 'sm:border-l sm:border-[#ecebea]' : ''">
                                    <span class="text-[0.68rem] font-extrabold uppercase text-gray-500" x-text="m[0]"></span>
                                    <strong class="text-[0.95rem]" x-text="m[1]"></strong>
                                </div>
                            </template>
                        </div>

                        <div class="grid sm:grid-cols-2">
                            {{-- Earnings --}}
                            <section class="min-w-0 border-t border-[#ecebea] p-3">
                                <h3 class="mb-2.5 text-[0.78rem] uppercase tracking-[0.08em] text-[#b22222]">Earnings</h3>
                                <div class="{{ $row }}"><span>Basic salary</span><strong class="min-w-[120px] text-center text-gray-900" x-text="money(preview.basicPay)"></strong></div>
                                <div class="{{ $row }}"><span>Daily rate</span><strong class="min-w-[120px] text-center text-gray-900" x-text="money(preview.step.dailyWage)"></strong></div>
                                {{-- Honorarium is fixed (administrative role only), not editable --}}
                                <div class="{{ $row }}"><span>Honorarium</span><strong class="min-w-[120px] text-center text-gray-900" x-text="draft.isAdmin ? money(preview.honorarium) : '-'"></strong></div>
                                <div class="mt-2 flex min-h-[30px] items-center justify-between gap-3 text-[0.85rem] font-extrabold text-gray-900"><span>Gross earnings</span><strong class="min-w-[120px] text-center text-[#b22222]" x-text="money(preview.grossPay)"></strong></div>
                            </section>

                            {{-- Deductions --}}
                            <section class="min-w-0 border-t border-[#ecebea] p-3 sm:border-l">
                                <h3 class="mb-2.5 text-[0.78rem] uppercase tracking-[0.08em] text-[#b22222]">Deductions</h3>

                                {{-- Read-only (computed automatically; SSS, PhilHealth, HDMF, PERAA/CEAP on the 30th only) --}}
                                <template x-for="r in computedFields" :key="r[1]">
                                    <div class="{{ $row }}">
                                        <span x-text="r[0]"></span>
                                        <span class="min-w-[120px] text-center font-bold text-gray-900"
                                            x-text="(r[2] && cutoff !== '30th') ? '-' : money(preview.deductions[r[1]])"></span>
                                    </div>
                                </template>

                                {{-- Editable: loans and cash advance --}}
                                <template x-for="r in dedFields" :key="r[1]">
                                    <div class="{{ $row }}">
                                        <span x-text="r[0]"></span>

                                        <span x-show="!isActive(r[1])" class="min-w-[120px] text-center font-bold text-gray-400">-</span>

                                        <input x-show="isActive(r[1])" type="number" min="0" step="0.01"
                                            :value="Number(draft.deductions[r[1]] || 0).toFixed(2)"
                                            @input="draft.deductions[r[1]] = Math.max(0, Number($event.target.value) || 0)"
                                            class="min-h-[34px] w-[120px] rounded-lg border border-[#ecebea] px-2 text-center font-bold text-gray-900">
                                    </div>
                                </template>

                                <p x-show="Number(draft.deductions.cashAdvance) > CASH_ADVANCE_CAP"
                                    class="mt-1 text-[0.72rem] font-semibold text-gray-500"
                                    x-text="'Only ' + money(CASH_ADVANCE_CAP) + ' of the cash advance is deducted this pay period.'"></p>

                                <div class="mt-2 flex min-h-[30px] items-center justify-between gap-3 text-[0.85rem] font-extrabold text-gray-900"><span>Total deductions</span><strong class="min-w-[120px] text-center text-[#b22222]" x-text="'- ' + money(preview.totalDeductions)"></strong></div>
                            </section>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t-2 border-[#b22222] bg-[#fef2f2] px-5 py-[18px] font-black tracking-[0.08em] text-[#b22222]">
                            <span>NET PAY</span>
                            <strong class="text-[1.25rem] tracking-normal" x-text="money(preview.netPay)"></strong>
                        </div>
                    </div>
                </template>

                <div class="mt-3 flex justify-between gap-2.5">
                    <span>
                        Received by:
                        <span class="inline-block min-w-[180px] border-b border-gray-500 ml-2"></span>
                    </span>
                    <div>
                        <button type="button" class="{{ $btn }}" @click="closeAll()">Cancel</button>
                        <button type="button" class="{{ $btn }}" @click="saveDetails()">Save changes</button>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        function payrollProcessing(cfg) {
            const SCHEDULED_WORKDAYS = 22;
            const MONTHLY_DAYS = 26;             // monthly wage = daily wage x 26
            const LATE_PER_MINUTE = 1.80;
            const MALASAKITAN = 50;
            const HONORARIUM = 1924.31;          // administrative role only
            const CASH_ADVANCE_CAP = 2000;       // max deduction per pay period
            // 30th pay period only. Base = monthly wage (daily wage x 26)
            const RATES = { sss: 0.05, hdmf: 0.02, philhealth: 0.025, peraaCeap: 0.04 };

            return {
                CASH_ADVANCE_CAP,
                employees: cfg.employees,
                steps: cfg.steps,
                cutoff: cfg.cutoff,
                period: cfg.period,

                saveState: 'idle',   // idle | saving | saved | error
                saveTimer: null,
                dirty: false,

                stepsOpen: false,
                stepsDraft: [],
                stepsError: '',
                savingSteps: false,

                activeIndex: null,
                draft: null,

                // ---- row selection (controls the Transfer button) ----
                selected: [],

                get allSelected() {
                    return this.employees.length > 0 && this.selected.length === this.employees.length;
                },
                toggleAll(checked) {
                    this.selected = checked ? this.employees.map((e) => e.id) : [];
                },
                toggleOne(id, checked) {
                    this.selected = checked
                        ? [...new Set([...this.selected, id])]
                        : this.selected.filter((x) => x !== id);
                },

                // [label, key, only30] computed automatically, read-only
                computedFields: [
                    ['MALASAKITAN', 'fixed50', false], ['Late', 'late', false], ['Absences', 'absences', false],
                    ['SSS', 'sss', true], ['PhilHealth', 'philhealth', true],
                    ['HDMF', 'hdmf', true], ['PERAA/CEAP', 'peraaCeap', true],
                ],

                // [label, key] editable deductions (loans are deducted on the 30th pay period only)
                dedFields: [
                    ['SSS Loan', 'sssLoan'], ['PhilHealth Loan', 'philhealthLoan'],
                    ['PERAA/CEAP Loan', 'peraaCeapLoan'], ['HDMF Loan', 'hdmfLoan'],
                    ['Cash advance balance (max \u20B12,000 per pay period)', 'cashAdvance'],
                ],

                money(v) {
                    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v) || 0);
                },

                // ---- calculation (same rules as the server; the server recalculates on transfer) ----
                calc(e) {
                    const step = this.steps[Number(e.step) - 1] || { name: 'Unassigned', dailyWage: 0 };
                    const wage = Number(step.dailyWage);
                    const days = Number(e.daysWorked) || 0;
                    const hours = Number(e.hoursWorked) || 0;

                    const absences = Math.max(0, SCHEDULED_WORKDAYS - days) * wage;
                    const missing = Math.max(0, days * 8 * 60 - hours * 60);
                    const late = Number(e.lateMinutes) || 0;
                    const effLate = Math.max(0, Math.min(late, missing || late));
                    const lateDed = effLate * LATE_PER_MINUTE;

                    const ratio = days > 0 ? Math.min(Math.max(hours / days / 8, 0), 1) : 0;
                    const basicPay = wage * days * ratio;
                    const honorarium = e.isAdmin ? HONORARIUM : 0;
                    const grossPay = basicPay + honorarium;

                    const d = e.deductions || {};
                    const g = (k) => Math.max(0, Number(d[k]) || 0);
                    const is30 = this.cutoff === '30th';
                    const monthly = wage * MONTHLY_DAYS;
                    const c = (k) => is30 ? monthly * RATES[k] : 0;

                    const deductions = {
                        fixed50: MALASAKITAN, late: lateDed, absences,
                        sss: c('sss'), philhealth: c('philhealth'), hdmf: c('hdmf'), peraaCeap: c('peraaCeap'),
                        sssLoan: is30 ? g('sssLoan') : 0,
                        philhealthLoan: is30 ? g('philhealthLoan') : 0,
                        peraaCeapLoan: is30 ? g('peraaCeapLoan') : 0,
                        hdmfLoan: is30 ? g('hdmfLoan') : 0,
                        cashAdvance: Math.min(CASH_ADVANCE_CAP, g('cashAdvance')),
                    };
                    const totalDeductions = Object.values(deductions).reduce((a, b) => a + b, 0);

                    return { step, monthlyWage: monthly, basicPay, honorarium, grossPay, deductions, totalDeductions, netPay: grossPay - totalDeductions };
                },

                get preview() { return this.draft ? this.calc(this.draft) : null; },

                // loans: 30th only. Cash advance: both pay periods.
                isActive(field) { return this.cutoff === '30th' || field === 'cashAdvance'; },

                // ---- table inputs: days / hours / late stay consistent with each other ----
                setDays(e, v, el) {
                    e.daysWorked = Math.max(0, Number(v) || 0);
                    const std = e.daysWorked * 8;
                    e.hoursWorked = Math.min(Math.max(Number(e.hoursWorked) || std, 0), std);
                    e.lateMinutes = Math.max(0, (std - e.hoursWorked) * 60);
                    this.syncRow(e, el);
                },
                setHours(e, v, el) {
                    const std = (Number(e.daysWorked) || 0) * 8;
                    const h = Math.max(0, Number(v) || 0);
                    e.hoursWorked = Math.min(h, std || h);
                    e.lateMinutes = Math.max(0, (std - e.hoursWorked) * 60);
                    this.syncRow(e, el);
                },
                setLate(e, v, el) {
                    const std = (Number(e.daysWorked) || 0) * 8;
                    const l = Math.max(0, Number(v) || 0);
                    e.lateMinutes = Math.min(l, std * 60 || l);
                    e.hoursWorked = Math.max(0, std - e.lateMinutes / 60);
                    this.syncRow(e, el);
                },
                // Update the sibling inputs without re-rendering the one being typed in.
                // Only number inputs: the row also contains the select checkbox.
                syncRow(e, el) {
                    const inputs = el.closest('tr').querySelectorAll('input[type="number"]');
                    [e.daysWorked, e.hoursWorked, e.lateMinutes].forEach((val, k) => {
                        if (inputs[k] !== el) inputs[k].value = val;
                    });
                    this.queueSave();
                },

                // ---- payslip modal ----
                openDetails(i) {
                    this.activeIndex = i;
                    this.draft = JSON.parse(JSON.stringify(this.employees[i]));
                },
                saveDetails() {
                    if (this.activeIndex === null) return;
                    const e = this.employees[this.activeIndex];
                    e.step = Math.max(1, Number(this.draft.step) || 1);
                    e.deductions = { ...this.draft.deductions };
                    this.queueSave();
                    this.closeAll();
                },

                // ---- steps modal ----
                openSteps() {
                    this.stepsDraft = this.steps.map((s) => ({ ...s }));
                    this.stepsError = '';
                    this.stepsOpen = true;
                },
                async saveSteps() {
                    this.savingSteps = true;
                    this.stepsError = '';
                    try {
                        const res = await fetch(cfg.stepsUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': cfg.csrf,
                            },
                            body: JSON.stringify({
                                steps: this.stepsDraft.map((s, i) => ({
                                    name: (s.name || '').trim() || 'Step ' + (i + 1),
                                    dailyWage: Math.max(0, Number(s.dailyWage) || 0),
                                })),
                            }),
                        });
                        if (!res.ok) throw new Error('Could not save the steps.');
                        this.steps = (await res.json()).steps;
                        this.employees.forEach((e) => { if (!this.steps[Number(e.step) - 1]) e.step = 1; });
                        this.queueSave();
                        this.stepsOpen = false;
                    } catch (err) {
                        this.stepsError = err.message;
                    } finally {
                        this.savingSteps = false;
                    }
                },

                closeAll() {
                    this.stepsOpen = false;
                    this.draft = null;
                    this.activeIndex = null;
                },

                // ---- autosave (payroll_records) ----
                get saveLabel() {
                    return { idle: '', saving: 'Saving...', saved: 'All changes saved', error: 'Not saved. Check your connection.' }[this.saveState];
                },

                // Contributions and honorarium are computed server-side; only manual fields are sent
                rowsPayload() {
                    return this.employees.map((e) => ({
                        employee_id: e.id, step_no: e.step,
                        days_worked: Number(e.daysWorked) || 0,
                        hours_worked: Number(e.hoursWorked) || 0,
                        late_minutes: Number(e.lateMinutes) || 0,
                        sss_loan: Number(e.deductions.sssLoan) || 0,
                        philhealth_loan: Number(e.deductions.philhealthLoan) || 0,
                        peraa_ceap_loan: Number(e.deductions.peraaCeapLoan) || 0,
                        hdmf_loan: Number(e.deductions.hdmfLoan) || 0,
                        cash_advance: Number(e.deductions.cashAdvance) || 0,
                    }));
                },

                queueSave() {
                    this.dirty = true;
                    this.saveState = 'saving';
                    clearTimeout(this.saveTimer);
                    this.saveTimer = setTimeout(() => this.flushSave(), 700);
                },

                // Resolves true when everything is saved
                async flushSave() {
                    clearTimeout(this.saveTimer);
                    if (!this.dirty || this.employees.length === 0) return true;
                    this.dirty = false;
                    try {
                        const res = await fetch(cfg.saveUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                            body: JSON.stringify({ period: this.period, cutoff: this.cutoff, rows: this.rowsPayload() }),
                        });
                        if (!res.ok) throw new Error('save failed');
                        if (!this.dirty) this.saveState = 'saved';
                        return true;
                    } catch (err) {
                        this.dirty = true;
                        this.saveState = 'error';
                        return false;
                    }
                },

                // ---- switching month / pay period reloads the page for that payroll run ----
                async go() {
                    if (!(await this.flushSave())) return;
                    window.location.href = cfg.pageUrl + '?period=' + encodeURIComponent(this.period) + '&cutoff=' + this.cutoff;
                },
                setCutoff(c) {
                    if (c === this.cutoff) return;
                    this.cutoff = c;
                    this.go();
                },

                async transfer(event) {
                    if (this.selected.length === 0) return;
                    if (!confirm('Transfer ' + this.selected.length + ' selected payslip(s) (' + this.cutoff + ' pay period, ' + this.period + ') to Salary Monitoring?')) return;
                    if (!(await this.flushSave())) { alert('Your changes could not be saved, so nothing was transferred.'); return; }
                    event.target.submit();
                },
            };
        }
    </script>
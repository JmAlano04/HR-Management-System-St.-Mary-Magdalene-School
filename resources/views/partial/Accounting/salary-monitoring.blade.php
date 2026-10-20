{{-- resources/views/salary-monitoring/index.blade.php --}}


    <x-slot name="title">
        Salary Monitoring
    </x-slot>

    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none ml-[80px] tracking-tight text-[#b22222] sm:text-4xl">Salary Monitoring</h1>
    </x-slot>

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="salaryMonitoring(@js([
            'rows'  => $rows,
            'slips' => $cards,
        ]))"
        @keydown.escape.window="viewId = null; reasonFor = null"
    >
        <section aria-labelledby="salaryMonitoringTitle"
            class="rounded-[22px] border border-[#ecebea] bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

            {{-- Heading + send buttons --}}
            <form method="POST" action="{{ route('salary-monitoring.send') }}" x-ref="sendForm">
                @csrf
                <input type="hidden" name="role" x-ref="role">
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>

                <div class="flex flex-col items-start justify-between gap-4 px-6 py-[22px] sm:flex-row sm:items-center">
                    <h2 id="salaryMonitoringTitle" class="text-[1.35rem] font-bold text-gray-900">Payslips</h2>

                    <div class="flex flex-wrap items-center gap-[7px]">
                        <template x-for="b in buttons" :key="b.role">
                            <button type="button" @click="send(b.role)" :disabled="!canSend(b.role)" :title="hint(b.role)"
                                class="min-h-[32px] cursor-pointer rounded-lg border px-2.5 text-[0.72rem] font-extrabold text-white disabled:cursor-default disabled:opacity-90"
                                :class="allSent(b.role) && !hasSelectedToSend(b.role)
                                    ? 'border-[#99d6aa] bg-[#2f9e44]'
                                    : 'border-[#f1caca] bg-[#b22222] hover:bg-[#8d1111]'"
                                x-text="label(b)"></button>
                        </template>
                    </div>
                </div>
            </form>

            {{-- Flash messages --}}
            <div class="px-6 empty:hidden">
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
                @endif
                @if ($errors->has('send') || $errors->has('ids'))
                    <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                        {{ $errors->first('send') ?: $errors->first('ids') }}
                    </div>
                @endif
            </div>

            {{-- Select all --}}
            <div x-show="rows.length" class="mx-[18px] mb-3 flex items-center justify-between gap-3 rounded-xl border border-[#ecebea] bg-[#f9fafb] px-3.5 py-2.5">
                <label class="inline-flex cursor-pointer items-center gap-2 text-[0.82rem] font-bold text-gray-900">
                    <input type="checkbox" x-ref="all" @change="toggleAll($event.target.checked)"
                        :checked="selected.length === rows.length && rows.length > 0"
                        class="h-[18px] w-[18px] cursor-pointer accent-[#b22222]">
                    Select all
                </label>
                <span class="text-[0.78rem] font-bold text-gray-500" x-text="selected.length + ' selected'"></span>
            </div>

            {{-- Rows --}}
            <div class="grid gap-2 px-[18px] pb-5">
                <template x-for="r in rows" :key="r.id">
                    <article class="grid items-center gap-4 rounded-xl border px-3.5 py-3 lg:grid-cols-[minmax(0,1fr)_auto]"
                        :class="selected.includes(r.id) ? 'border-[#d9a3a3] bg-[#fef2f2]' : 'border-[#ecebea] bg-white'">

                        <div class="grid min-w-0 items-center gap-5 py-1 sm:grid-cols-[auto_minmax(180px,1fr)_auto_auto]">
                            <input type="checkbox" :value="r.id" x-model.number="selected"
                                :aria-label="'Select payslip for ' + r.name"
                                class="h-[18px] w-[18px] cursor-pointer accent-[#b22222]">

                            <span class="grid gap-[3px]">
                                <button type="button" @click="viewId = r.id"
                                    class="cursor-pointer text-left font-bold text-[#b22222] hover:underline" x-text="r.name"></button>
                                <small class="text-[0.72rem] text-gray-500" x-text="r.number + ' | ' + r.dept"></small>
                            </span>

                            <span class="grid gap-[3px] sm:text-right">
                                <small class="text-[0.72rem] text-gray-500">Net pay</small>
                                <strong class="text-[0.95rem]" x-text="money(r.net)"></strong>
                            </span>

                            <span class="min-w-[48px] text-center text-[0.78rem] font-extrabold text-gray-500" x-text="r.cutoff"></span>
                        </div>

                        <div class="flex flex-wrap gap-1.5 lg:justify-end">
                            <template x-for="role in ['admin', 'hr', 'employee']" :key="role">
                                <button type="button"
                                    class="inline-flex min-h-[30px] items-center gap-[5px] rounded-full border px-[9px] text-[0.7rem] font-extrabold"
                                    :class="[badge(r, role).css, badge(r, role).rejected ? 'cursor-pointer' : 'cursor-default']"
                                    @click="badge(r, role).rejected && (reasonFor = { r, role })">
                                    <span class="h-[7px] w-[7px] rounded-full" :class="badge(r, role).dot"></span>
                                    <span x-text="badge(r, role).text"></span>
                                </button>
                            </template>
                        </div>
                    </article>
                </template>

                <div x-show="rows.length === 0"
                    class="rounded-xl border border-dashed border-gray-300 px-5 py-8 text-center font-semibold text-gray-500">
                    Transfer payroll from Payroll Processing to view payslips.
                </div>
            </div>

            @if ($payslips->hasPages())
                <div class="px-[18px] pb-5">{{ $payslips->links() }}</div>
            @endif
        </section>

        {{-- ============ Payslip modal ============ --}}
        <div x-show="slip" x-cloak x-transition.opacity @click.self="viewId = null"
            class="fixed inset-0 z-[60] grid place-items-center bg-[rgba(17,24,39,0.48)] p-5">
            <section role="dialog" aria-modal="true"
                class="relative max-h-[calc(100vh-24px)] w-[min(900px,calc(100vw-32px))] overflow-y-auto rounded-[22px] bg-white px-3 pb-3 pt-[34px] shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
                <button type="button" @click="viewId = null" aria-label="Close payslip"
                    class="absolute right-[18px] top-4 grid h-[38px] w-[38px] cursor-pointer place-items-center rounded-xl bg-gray-100 text-gray-900 transition hover:bg-gray-200">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

                <template x-if="slip">
                    <div class="grid gap-[18px] p-2 text-gray-900 sm:p-4">
                        <header class="flex flex-col items-center gap-1 border-b border-gray-200 pb-2.5 text-center">
                            <strong class="text-2xl" x-text="slip.employee_name"></strong>
                            <span class="text-gray-500" x-text="'ID: ' + slip.employee_number"></span>
                            <small class="text-gray-500" x-text="slip.department + ' | ' + slip.position + ' | ' + slip.step_name"></small>
                        </header>

                        <div class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-gray-200 bg-gray-200 sm:grid-cols-4">
                            <template x-for="m in [['Days worked', Number(slip.days_worked)], ['Hours worked', Number(slip.hours_worked)], ['Late (mins)', slip.late_minutes], ['Cutoff', slip.cutoff]]" :key="m[0]">
                                <div class="flex flex-col items-center justify-center gap-1.5 bg-gray-50 px-2.5 py-3 text-center">
                                    <span class="text-[0.72rem] font-bold uppercase tracking-[0.05em] text-gray-500" x-text="m[0]"></span>
                                    <strong class="text-xl" x-text="m[1]"></strong>
                                </div>
                            </template>
                        </div>

                        <div class="grid overflow-hidden rounded-[14px] border border-gray-200 sm:grid-cols-2">
                            <section class="min-w-0 content-start p-[18px]">
                                <h3 class="mb-1 text-[0.95rem] font-bold uppercase tracking-[0.04em] text-[#b22222]">Earnings</h3>
                                <template x-for="e in [['Basic salary', slip.basic_pay], ['Daily rate', slip.daily_wage], ['Honorarium', slip.honorarium]]" :key="e[0]">
                                    <div class="mt-1 flex items-center justify-between gap-2.5 rounded-[10px] bg-gray-50 px-3 py-2.5">
                                        <span class="text-[0.8rem] text-gray-500" x-text="e[0]"></span>
                                        <strong class="text-[0.95rem]" x-text="money(e[1])"></strong>
                                    </div>
                                </template>
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

                        <div class="flex items-center justify-between rounded-xl border-t-2 border-[#b22222] bg-[#fef2f2] px-5 py-4 font-extrabold text-[#991b1b]">
                            <span>NET PAY</span>
                            <strong class="text-[1.55rem]" x-text="money(slip.net_pay)"></strong>
                        </div>
                    </div>
                </template>
            </section>
        </div>

        {{-- ============ Rejection reason modal ============ --}}
        <div x-show="reasonFor" x-cloak x-transition.opacity @click.self="reasonFor = null"
            class="fixed inset-0 z-[70] grid place-items-center bg-[rgba(17,24,39,0.48)] p-5">
            <section role="dialog" aria-modal="true"
                class="relative w-[min(480px,calc(100vw-32px))] rounded-[22px] bg-white p-7 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
                <button type="button" @click="reasonFor = null" aria-label="Close"
                    class="absolute right-3 top-2.5 cursor-pointer text-2xl text-gray-500 hover:text-[#b22222]">&times;</button>
                <h2 class="mb-2 mr-10 text-xl font-bold text-gray-900">Rejection reason</h2>
                <p class="mb-3.5 text-[0.9rem] font-bold text-gray-500"
                    x-text="reasonFor ? reasonFor.r.name + ' — ' + (reasonFor.role === 'hr' ? 'HR' : 'Admin') : ''"></p>
                <p class="whitespace-pre-wrap break-words rounded-xl border border-[#fecaca] bg-[#fef2f2] p-3.5 leading-relaxed text-[#7f1d1d]"
                    x-text="(reasonFor && reasonFor.r[reasonFor.role].reason) || 'No rejection reason was provided.'"></p>
            </section>
        </div>
    </div>

    <script>
        function salaryMonitoring(cfg) {
            return {
                rows: cfg.rows,
                slips: cfg.slips,
                selected: [],
                viewId: null,
                reasonFor: null,

                buttons: [
                    { role: 'admin',    text: 'Send to Admin & HR' },
                    { role: 'employee', text: 'Send to Employees' },
                ],

                deductionRows: [
                    ['MALASAKITAN', 'malasakitan'], ['Late', 'late'], ['Absences', 'absences'],
                    ['SSS', 'sss'], ['SSS LOAN', 'sss_loan'], ['PhilHealth', 'philhealth'],
                    ['PERAA/CEAP', 'peraa_ceap'], ['PERAA/CEAP LOAN', 'peraa_ceap_loan'],
                    ['HDMF', 'hdmf'], ['HDMF LOAN', 'hdmf_loan'], ['Cash advance', 'cash_advance'],
                ],

                get slip() { return this.viewId ? this.slips[this.viewId] : null; },

                money(v) {
                    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v) || 0);
                },

                toggleAll(on) { this.selected = on ? this.rows.map((r) => r.id) : []; },

                // ---- send rules (the server re-checks all of these) ----
                rejected(r) { return r.admin.status === 'Rejected' || r.hr.status === 'Rejected'; },
                needsSend(r, role) {
                    if (role === 'admin') return !r.admin.sent || this.rejected(r);
                    return !r.employee.sent;
                },
                selectedToSend(role) {
                    return this.rows.filter((r) => this.selected.includes(r.id) && this.needsSend(r, role));
                },
                hasSelectedToSend(role) { return this.selectedToSend(role).length > 0; },
                allSent(role) { return this.rows.length > 0 && this.rows.every((r) => !this.needsSend(r, role)); },
                isResend(role) {
                    return role === 'admin' && this.selectedToSend(role).some((r) => r.admin.sent);
                },
                ready(role) {
                    if (role === 'employee') {
                        return this.selectedToSend(role).every((r) => r.admin.status === 'Approved' && r.hr.status === 'Approved');
                    }
                    return true;
                },
                canSend(role) { return this.hasSelectedToSend(role) && this.ready(role); },

                label(b) {
                    if (this.isResend(b.role)) return b.text.replace('Send to', 'Resend to');
                    if (this.allSent(b.role) && !this.hasSelectedToSend(b.role)) return 'Sent';
                    return b.text;
                },
                hint(role) {
                    if (this.allSent(role)) return 'All payslips have already been sent';
                    if (!this.hasSelectedToSend(role)) return 'Select payslips that have not been sent or were rejected';
                    if (!this.ready(role)) return 'Admin and HR approval is required for the selected payslips';
                    return '';
                },

                send(role) {
                    if (!this.canSend(role)) return;
                    this.$refs.role.value = role;
                    this.$refs.sendForm.submit();
                },

                // ---- approval badges ----
                badge(r, role) {
                    const name = { admin: 'Admin', hr: 'HR', employee: 'Employee' }[role];
                    const sent = r[role].sent;
                    let status, css, dot, rejected = false;

                    if (role === 'employee') {
                        status = sent ? 'Approved' : 'Not yet sent';
                    } else if (r[role].status === 'Rejected') {
                        status = 'Rejected'; rejected = true;
                    } else if (!sent) {
                        status = 'Not yet sent';
                    } else {
                        status = r[role].status === 'Approved' ? 'Approved' : 'Pending';
                    }

                    if (status === 'Approved') { css = 'border-[#a7d7b1] bg-[#f0faf2] text-[#217a38]'; dot = 'bg-[#2f9e44]'; }
                    else if (status === 'Rejected') { css = 'border-[#fecaca] bg-[#fef2f2] text-[#b91c1c] hover:bg-[#fee2e2]'; dot = 'bg-[#b91c1c]'; }
                    else if (status === 'Pending') { css = 'border-[#f0cf72] bg-[#fff8df] text-[#8a6500]'; dot = 'bg-[#d49a00]'; }
                    else { css = 'border-dashed border-[#ecebea] bg-white text-gray-500'; dot = 'bg-gray-400'; }

                    return { text: name + ': ' + status, css, dot, rejected };
                },
            };
        }
    </script>

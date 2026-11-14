{{-- resources/views/salary-monitoring/index.blade.php --}}


    <x-slot name="title">
       Accounting Salary Monitoring
    </x-slot>

    <x-slot name="header">
        <h1 class="ml-20 text-xl font-extrabold leading-none tracking-tight text-[#b22222] sm:ml-[80px] sm:text-4xl">Salary Monitoring</h1>
    </x-slot>

    <div
        class="px-3 pb-10 sm:px-6 sm:pb-12 lg:px-10"
        x-data="salaryMonitoring(@js([
            'rows'  => $rows,
            'slips' => $cards,
        ]))"
        @keydown.escape.window="viewId = null; reasonFor = null"
    >
        <section aria-labelledby="salaryMonitoringTitle"
            class="rounded-[18px] border border-[#ecebea] bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:rounded-[22px]">

            {{-- Heading + send buttons --}}
            <form method="POST" action="{{ route('salary-monitoring.send') }}" x-ref="sendForm">
                @csrf
                <input type="hidden" name="role" x-ref="role">
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>

                <div class="flex flex-col items-start justify-between gap-3 px-4 py-4 sm:flex-row sm:items-center sm:gap-4 sm:px-6 sm:py-[22px]">
                    <h2 id="salaryMonitoringTitle" class="text-xl font-bold text-gray-900 sm:text-[1.35rem]">Payslips</h2>

                    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:gap-[7px]">
                        <template x-for="b in buttons" :key="b.role">
                            <button type="button" x-show="b.role !== 'employee' || hasApprovedToSend()"
                                @click="send(b.role)" :disabled="!canSend(b.role)" :title="hint(b.role)"
                                class="min-h-[40px] flex-1 cursor-pointer rounded-lg border px-3 text-[0.78rem] font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-100 sm:min-h-[32px] sm:flex-none sm:px-2.5 sm:text-[0.72rem]"
                                :class="allSent(b.role) && !hasSelectedToSend(b.role)
                                    ? 'border-[#99d6aa] bg-[#2f9e44]'
                                    : canSend(b.role)
                                        ? 'border-[#f1caca] bg-[#b22222] hover:bg-[#8d1111]'
                                        : 'border-gray-300 bg-gray-300 text-gray-600'"
                                x-text="label(b)"></button>
                        </template>
                    </div>
                </div>
            </form>

            {{-- Flash messages --}}
            <div class="px-4 empty:hidden sm:px-6">
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
            <div x-show="rows.length" class="mx-3 mb-3 flex items-center justify-between gap-3 rounded-xl border border-[#ecebea] bg-[#f9fafb] px-3.5 py-2.5 sm:mx-[18px]">
                <label class="inline-flex cursor-pointer items-center gap-2 text-[0.82rem] font-bold text-gray-900">
                    <input type="checkbox" x-ref="all" @change="toggleAll($event.target.checked)"
                        :checked="selected.length === rows.length && rows.length > 0"
                        class="h-[18px] w-[18px] cursor-pointer accent-[#b22222]">
                    Select all
                </label>
                <span class="text-[0.78rem] font-bold text-gray-500" x-text="selected.length + ' selected'"></span>
            </div>

            {{-- Rows --}}
            <div class="grid gap-2 px-3 pb-4 sm:px-[18px] sm:pb-5">
                <template x-for="r in rows" :key="r.id">
                    <article class="grid items-center gap-2.5 rounded-xl border px-3 py-3 sm:gap-4 sm:px-3.5 lg:grid-cols-[minmax(0,1fr)_auto]"
                        :class="selected.includes(r.id) ? 'border-[#d9a3a3] bg-[#fef2f2]' : 'border-[#ecebea] bg-white'">

                        {{-- Mobile: [checkbox | name + number | net pay] with cutoff under net pay --}}
                        <div class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1 py-1 sm:grid-cols-[auto_minmax(180px,1fr)_auto_auto] sm:gap-5">
                            <input type="checkbox" :value="r.id" x-model.number="selected"
                                :aria-label="'Select payslip for ' + r.name"
                                class="h-[18px] w-[18px] cursor-pointer accent-[#b22222]">

                            <span class="grid min-w-0 gap-[3px]">
                                <button type="button" @click="viewId = r.id"
                                    class="cursor-pointer truncate text-left font-bold text-[#b22222] hover:underline" x-text="r.name"></button>
                                <small class="truncate text-[0.72rem] text-gray-500" x-text="r.number + ' | ' + r.dept"></small>
                            </span>

                            <span class="grid gap-[3px] text-right">
                                <small class="text-[0.72rem] text-gray-500">Net pay</small>
                                <strong class="text-[0.95rem]" x-text="money(r.net)"></strong>
                            </span>

                            <span class="col-start-3 min-w-[48px] text-right text-[0.78rem] font-extrabold text-gray-500 sm:col-auto sm:text-center" x-text="r.cutoff"></span>
                        </div>

                        {{-- Badges: aligned under the name on mobile --}}
                        <div class="flex flex-wrap gap-1.5 pl-[30px] sm:pl-[38px] lg:justify-end lg:pl-0">
                            <template x-for="role in ['hr', 'admin']" :key="role">
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
                <div class="overflow-x-auto px-3 pb-5 sm:px-[18px]">{{ $payslips->links() }}</div>
            @endif
        </section>

        {{-- ============ Payslip modal ============ --}}
        <div x-show="slip" x-cloak x-transition.opacity @click.self="viewId = null"
            class="fixed inset-0 z-[60] grid place-items-center overflow-y-auto bg-[rgba(17,24,39,0.48)] p-2 sm:p-5">
            <section role="dialog" aria-modal="true"
                class="relative flex max-h-[calc(100dvh-16px)] w-[min(820px,calc(100vw-16px))] flex-col rounded-[20px] bg-white px-2 pb-2 pt-2 shadow-[0_20px_50px_rgba(15,23,42,0.08)] sm:max-h-[calc(100dvh-40px)] sm:w-[min(820px,calc(100vw-40px))] sm:rounded-[24px] sm:px-3 sm:pb-3">
                <button type="button" @click="viewId = null" aria-label="Close payslip"
                    class="absolute right-2 top-2 z-10 grid h-9 w-9 cursor-pointer place-items-center rounded-full bg-white/90 text-xl text-gray-500 transition hover:bg-[#fef2f2] hover:text-[#b22222] sm:right-3 sm:top-3 sm:h-8 sm:w-8">
                    &times;
                </button>

                <template x-if="slip">
                    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain rounded-[16px] bg-white sm:rounded-[18px]">
                        <div class="flex flex-col items-center gap-0.5 px-10 pb-3 pt-3 text-center sm:px-4">
                            <strong class="break-words text-[clamp(1.25rem,5vw,2.25rem)] font-extrabold leading-tight text-gray-900" x-text="slip.employee_name"></strong>
                            <span class="text-[0.82rem] text-gray-800" x-text="'ID: ' + slip.employee_number"></span>
                            <span class="text-[0.78rem] text-gray-500 sm:text-[0.82rem]" x-text="slip.department + ' | ' + slip.position + ' | ' + slip.step_name"></span>
                        </div>

                        {{-- Mobile: Date full width, then 2x2 grid. Desktop: 5 columns --}}
                        <div class="grid grid-cols-2 border-y border-[#ecebea] sm:grid-cols-5">
                            <template x-for="(m, k) in [
                                ['Date', slip.cutoff_label || slip.cutoff],
                                ['Days worked', Number(slip.days_worked)],
                                ['Hours worked', Number(slip.hours_worked)],
                                ['Late (mins)', Number(slip.late_minutes)],
                                ['Monthly wage', money((Number(slip.daily_wage) || 0) * 26)]
                            ]" :key="k">
                                <div class="grid justify-items-center gap-[3px] px-1.5 py-2.5 text-center"
                                    :class="[
                                        k === 0 ? 'col-span-2 sm:col-span-1' : 'border-t border-[#ecebea] sm:border-l sm:border-t-0',
                                        (k === 2 || k === 4) ? 'border-l border-[#ecebea]' : ''
                                    ]">
                                    <span class="text-[0.66rem] font-extrabold uppercase tracking-[0.08em] text-gray-500 sm:text-[0.68rem]" x-text="m[0]"></span>
                                    <strong class="break-words text-[0.9rem] text-gray-900 sm:text-[0.95rem]" x-text="m[1]"></strong>
                                </div>
                            </template>
                        </div>

                        <div class="grid sm:grid-cols-2">
                            <section class="min-w-0 border-t border-[#ecebea] p-3">
                                <h3 class="mb-2 text-[0.78rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Earnings</h3>
                                <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.82rem] text-gray-500 sm:min-h-[30px] sm:text-[0.85rem]">
                                    <span>Basic salary</span>
                                    <strong class="min-w-[96px] text-right text-gray-900 sm:min-w-[120px] sm:text-center" x-text="money(slip.basic_pay)"></strong>
                                </div>
                                <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.82rem] text-gray-500 sm:min-h-[30px] sm:text-[0.85rem]">
                                    <span>Daily rate</span>
                                    <strong class="min-w-[96px] text-right text-gray-900 sm:min-w-[120px] sm:text-center" x-text="money(slip.daily_wage)"></strong>
                                </div>
                                <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.82rem] text-gray-500 sm:min-h-[30px] sm:text-[0.85rem]">
                                    <span>Honorarium</span>
                                    <strong class="min-w-[96px] text-right text-gray-900 sm:min-w-[120px] sm:text-center" x-text="Number(slip.honorarium) > 0 ? money(slip.honorarium) : '-'"></strong>
                                </div>
                                <div class="mt-3 flex min-h-[30px] items-center justify-between gap-3 text-[0.85rem] font-extrabold text-gray-900">
                                    <span>Gross earnings</span>
                                    <strong class="min-w-[96px] text-right text-[#b22222] sm:min-w-[120px] sm:text-center" x-text="money(slip.gross_pay)"></strong>
                                </div>
                            </section>

                            <section class="min-w-0 border-t border-[#ecebea] p-3 sm:border-l">
                                <h3 class="mb-2 text-[0.78rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Deductions</h3>
                                <template x-for="row in deductionRows" :key="row[1]">
                                    <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.82rem] text-gray-500 sm:min-h-[30px] sm:text-[0.85rem]">
                                        <span x-text="row[0]"></span>
                                        <strong class="min-w-[96px] text-right text-gray-900 sm:min-w-[120px] sm:text-center" x-text="money(slip.deductions[row[1]])"></strong>
                                    </div>
                                </template>
                                <div class="mt-3 flex min-h-[30px] items-center justify-between gap-3 text-[0.85rem] font-extrabold text-gray-900">
                                    <span>Total deductions</span>
                                    <strong class="min-w-[96px] text-right text-[#b22222] sm:min-w-[120px] sm:text-center" x-text="'- ' + money(slip.total_deductions)"></strong>
                                </div>
                            </section>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t-2 border-[#b22222] bg-[#fef2f2] px-4 py-3 font-black tracking-[0.08em] text-[#b22222] sm:px-5">
                            <span>NET PAY</span>
                            <strong class="text-lg tracking-normal" x-text="money(slip.net_pay)"></strong>
                        </div>
                    </div>
                </template>
            </section>
        </div>

        {{-- ============ Rejection reason modal ============ --}}
        <div x-show="reasonFor" x-cloak x-transition.opacity @click.self="reasonFor = null"
            class="fixed inset-0 z-[70] grid place-items-center overflow-y-auto bg-[rgba(17,24,39,0.48)] p-3 sm:p-5">
            <section role="dialog" aria-modal="true"
                class="relative max-h-[calc(100dvh-24px)] w-[min(480px,calc(100vw-24px))] overflow-y-auto rounded-[20px] bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] sm:rounded-[22px] sm:p-7">
                <button type="button" @click="reasonFor = null" aria-label="Close"
                    class="absolute right-3 top-2.5 cursor-pointer text-2xl text-gray-500 hover:text-[#b22222]">&times;</button>
                <h2 class="mb-2 mr-10 text-lg font-bold text-gray-900 sm:text-xl">Rejection reason</h2>
                <p class="mb-3.5 text-[0.85rem] font-bold text-gray-500 sm:text-[0.9rem]"
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
                    ['MALASAKITAN', 'malasakitan'], ['Late', 'late'],
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
                approvedByAdminAndHr(r) {
                    return [r.admin.status, r.hr.status]
                        .every((status) => String(status || '').trim().toLowerCase() === 'approved');
                },
                hasApprovedToSend() {
                    return this.rows.some((r) => !r.employee.sent && this.approvedByAdminAndHr(r));
                },
                approvedToSendIds() {
                    return this.rows
                        .filter((r) => !r.employee.sent && this.approvedByAdminAndHr(r))
                        .map((r) => r.id);
                },
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
                    if (role !== 'employee') return true;
                    const selected = this.selectedToSend(role);
                    return selected.length > 0 && selected.every((r) => this.approvedByAdminAndHr(r));
                },
                canSend(role) {
                    return role === 'employee'
                        ? this.hasApprovedToSend()
                        : this.hasSelectedToSend(role) && this.ready(role);
                },

                label(b) {
                    if (this.isResend(b.role)) return b.text.replace('Send to', 'Resend to');
                    if (this.allSent(b.role) && !this.hasSelectedToSend(b.role)) return 'Sent';
                    return b.text;
                },
                hint(role) {
                    if (this.allSent(role)) return 'All payslips have already been sent';
                    if (role === 'employee' && this.hasApprovedToSend()) {
                        return 'Send all payslips approved by both Admin and HR';
                    }
                    if (!this.hasSelectedToSend(role)) {
                        return role === 'employee'
                            ? 'Select payslips approved by both Admin and HR'
                            : 'Select payslips that have not been sent or were rejected';
                    }
                    if (!this.ready(role)) return 'Admin and HR approval is required for the selected payslips';
                    return '';
                },

                send(role) {
                    if (!this.canSend(role)) return;
                    if (role === 'employee') this.selected = this.approvedToSendIds();
                    this.$refs.role.value = role;
                    this.$nextTick(() => this.$refs.sendForm.submit());
                },

                // ---- approval badges ----
                badge(r, role) {
                    const name = { admin: 'Admin', hr: 'HR' }[role];
                    const sent = r[role].sent;
                    let status, css, dot, rejected = false;

                    if (r[role].status === 'Rejected') {
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
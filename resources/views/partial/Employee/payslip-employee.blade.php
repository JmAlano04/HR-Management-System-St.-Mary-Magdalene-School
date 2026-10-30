<x-slot name="header">
    <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-4xl font-extrabold leading-tight sm:ml-[10px] md:ml-[20px] lg:ml-[80px] text-red-700">Payslip</h1>
</x-slot>


<div class="px-4 pb-12 sm:px-6 lg:px-10"
     x-data="{
        months: @js($months),
        month: null,
        slip: null,
        rejecting: false,
        peso(v) { return '₱' + Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        get folder() { return this.months.find(m => m.key === this.month) || null; },
        openById(id) {
            for (const m of this.months) {
                const p = m.payslips.find(p => p.id === id);
                if (p) { this.month = m.key; this.slip = p; return; }
            }
        },
        close() { this.rejecting ? this.rejecting = false : this.slip = null; },
        badge(s) {
            return s === 'Approved' ? 'bg-[#e8f5ec] text-[#21683a]'
                 : s === 'Rejected' ? 'bg-[#fdeaea] text-[#b22222]'
                 : 'bg-[#fff5d6] text-[#8a5a00]';
        }
     }"
     x-init="@if ($openPayslipId) openById({{ (int) $openPayslipId }}) @endif"
     @keydown.escape.window="close()">

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-4 max-w-[1100px] rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 max-w-[1100px] rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mt-2 max-w-[1100px]">

        {{-- ============ Payslip folders panel ============ --}}
        <section class="rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Released payroll</p>
                    <h2 class="text-2xl font-bold text-gray-900">My Payslips</h2>
                    <p class="mt-2 text-gray-500">Open a month to view, approve, or reject your payslips.</p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <span class="hidden rounded-md bg-gray-100 px-2.5 py-1.5 text-[0.82rem] font-bold text-gray-600 sm:inline-flex">
                        {{ $totalReleased ? $totalReleased.' released '.Str::plural('payslip', $totalReleased) : 'No payslips released' }}
                    </span>
                    <span class="grid h-[42px] w-[42px] place-items-center rounded-lg bg-[#fef2f2] text-[#b22222]" aria-hidden="true">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </span>
                </div>
            </div>

            @if ($months->isEmpty())
                <div class="grid place-items-center gap-2 rounded-[14px] border border-dashed border-gray-300 px-4 py-10 text-center text-gray-500">
                    <i class="fa-regular fa-folder-open text-2xl" aria-hidden="true"></i>
                    <span>Your released payslips will appear here after Accounting sends them.</span>
                </div>
            @else
                {{-- Month folders --}}
                <div x-show="!month" class="grid gap-3 sm:grid-cols-2">
                    <template x-for="m in months" :key="m.key">
                        <button type="button" @click="month = m.key"
                            class="flex cursor-pointer items-center gap-3 rounded-[14px] border border-[#ecebea] bg-gray-50 px-4 py-3.5 text-left transition hover:border-[#b22222] hover:bg-[#fef2f2]">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[#fef2f2] text-[#b22222]">
                                <i class="fa-solid fa-folder" aria-hidden="true"></i>
                            </span>
                            <span class="grid min-w-0 flex-1">
                                <strong class="text-gray-900" x-text="m.label"></strong>
                                <small class="text-gray-500" x-text="m.payslips.length + (m.payslips.length === 1 ? ' payslip' : ' payslips')"></small>
                            </span>
                            <i class="fa-solid fa-chevron-right text-xs text-gray-400" aria-hidden="true"></i>
                        </button>
                    </template>
                </div>

                {{-- Payslips inside a month --}}
                <div x-show="month" x-cloak class="grid gap-3">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="month = null" aria-label="Back to months"
                            class="grid h-9 w-9 cursor-pointer place-items-center rounded-full bg-gray-100 text-gray-900 transition hover:bg-gray-200">
                            <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
                        </button>
                        <strong class="text-gray-900" x-text="folder?.label"></strong>
                    </div>

                    <ul class="grid list-none gap-2 p-0">
                        <template x-for="p in folder?.payslips || []" :key="p.id">
                            <li>
                                <button type="button" @click="slip = p"
                                    class="flex w-full cursor-pointer items-center gap-3 rounded-md border border-gray-200 bg-white px-3 py-2.5 text-left transition hover:border-[#b22222]">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#fef2f2] text-[#b22222]">
                                        <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                                    </span>
                                    <span class="grid min-w-0 flex-1">
                                        <strong class="text-sm text-gray-900" x-text="p.cutoff + ' Pay Period'"></strong>
                                        <small class="text-xs text-gray-500" x-text="'Net pay ' + peso(p.net_pay)"></small>
                                    </span>
                                    <span class="rounded-md px-2.5 py-1 text-[0.78rem] font-bold" :class="badge(p.status)" x-text="p.status"></span>
                                    <i class="fa-solid fa-chevron-right text-xs text-gray-400" aria-hidden="true"></i>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            @endif
        </section>
    </div>

    {{-- ============ Payslip modal ============ --}}
    <div x-show="slip" x-cloak x-transition.opacity class="fixed inset-0 z-[60] grid place-items-center overflow-y-auto p-5">
        <div class="absolute inset-0 bg-[rgba(17,24,39,0.55)]" @click="slip = null"></div>

        <section role="dialog" aria-modal="true" aria-labelledby="payslipTitle"
            class="relative my-auto w-full max-w-[640px] rounded-[20px] bg-white p-[26px] shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
            <button type="button" @click="slip = null" aria-label="Close payslip"
                class="absolute right-4 top-4 h-9 w-9 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>

            <template x-if="slip">
                <div class="grid gap-4">
                    <header class="grid gap-0.5 pr-10">
                        <strong id="payslipTitle" class="text-[1.2rem] text-gray-900" x-text="slip.employee.name"></strong>
                        <span class="text-sm text-gray-500" x-text="'ID: ' + slip.employee.number"></span>
                        <small class="text-xs text-gray-500"
                            x-text="slip.employee.department + ' | ' + slip.employee.position + ' | ' + (slip.step_name || '—')"></small>
                    </header>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div class="grid rounded-md border border-gray-200 bg-gray-50 px-3 py-2"><span class="text-xs text-gray-500">Pay period</span><strong class="text-sm" x-text="slip.cutoff"></strong></div>
                        <div class="grid rounded-md border border-gray-200 bg-gray-50 px-3 py-2"><span class="text-xs text-gray-500">Days worked</span><strong class="text-sm" x-text="slip.days"></strong></div>
                        <div class="grid rounded-md border border-gray-200 bg-gray-50 px-3 py-2"><span class="text-xs text-gray-500">Hours worked</span><strong class="text-sm" x-text="slip.hours"></strong></div>
                        <div class="grid rounded-md border border-gray-200 bg-gray-50 px-3 py-2"><span class="text-xs text-gray-500">Late (mins)</span><strong class="text-sm" x-text="slip.late_min"></strong></div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <section class="grid content-start gap-1.5 rounded-[14px] border border-[#ecebea] bg-gray-50 p-4 text-sm">
                            <h3 class="mb-1 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Earnings</h3>
                            <div class="flex justify-between"><span class="text-gray-500">Basic salary</span><strong x-text="peso(slip.basic_pay)"></strong></div>
                            <div class="flex justify-between"><span class="text-gray-500">Daily rate</span><strong x-text="peso(slip.daily_wage)"></strong></div>
                            <div class="flex justify-between"><span class="text-gray-500">Honorarium</span><strong x-text="peso(slip.honorarium)"></strong></div>
                            <div class="mt-1 flex justify-between border-t border-gray-200 pt-2"><span class="font-semibold text-gray-900">Gross earnings</span><strong x-text="peso(slip.gross_pay)"></strong></div>
                        </section>

                        <section class="grid content-start gap-1.5 rounded-[14px] border border-[#ecebea] bg-gray-50 p-4 text-sm">
                            <h3 class="mb-1 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Deductions</h3>
                            <template x-for="d in slip.deductions" :key="d[0]">
                                <div class="flex justify-between"><span class="text-gray-500" x-text="d[0]"></span><strong x-text="peso(d[1])"></strong></div>
                            </template>
                            <div class="mt-1 flex justify-between border-t border-gray-200 pt-2"><span class="font-semibold text-gray-900">Total deductions</span><strong x-text="peso(slip.total_deductions)"></strong></div>
                        </section>
                    </div>

                    <div class="flex items-center justify-between rounded-[14px] bg-[#b22222] px-4 py-3 text-white">
                        <span class="text-sm font-extrabold tracking-[0.08em]">NET PAY</span>
                        <strong class="text-xl" x-text="peso(slip.net_pay)"></strong>
                    </div>

                    {{-- Decision --}}
                    <template x-if="slip.status === 'Pending'">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-gray-500">You can respond only once.</p>
                            <div class="flex gap-2">
                                <button type="button" @click="rejecting = true"
                                    class="min-h-[38px] cursor-pointer rounded-md border border-[#b22222] bg-white px-4 py-2 font-bold text-[#b22222] transition hover:bg-[#b22222] hover:text-white">
                                    Reject
                                </button>
                                <form method="POST" :action="slip.action_url">
                                    @csrf
                                    <input type="hidden" name="decision" value="approve">
                                    <button type="submit"
                                        class="min-h-[38px] cursor-pointer rounded-md border border-[#b22222] bg-[#b22222] px-4 py-2 font-bold text-white transition hover:bg-white hover:text-[#b22222]">
                                        <i class="fa-solid fa-check mr-1" aria-hidden="true"></i> Approve
                                    </button>
                                </form>
                            </div>
                        </div>
                    </template>
                    <template x-if="slip.status !== 'Pending'">
                        <p role="status" class="rounded-lg border px-4 py-3 text-sm font-semibold"
                           :class="slip.status === 'Approved' ? 'border-green-300 bg-green-50 text-green-800' : 'border-[#f0dada] bg-[#fef2f2] text-[#b22222]'"
                           x-text="slip.status === 'Approved' ? 'Payslip approved' : 'Payslip rejected' + (slip.reason ? ': ' + slip.reason : '')"></p>
                    </template>
                </div>
            </template>
        </section>
    </div>

    {{-- ============ Rejection reason modal ============ --}}
    <div x-show="rejecting && slip" x-cloak x-transition.opacity class="fixed inset-0 z-[70] grid place-items-center p-5">
        <div class="absolute inset-0 bg-[rgba(17,24,39,0.55)]" @click="rejecting = false"></div>

        <section role="dialog" aria-modal="true" aria-labelledby="rejectPayslipTitle"
            class="relative w-full max-w-[480px] rounded-[20px] bg-white p-[26px] shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 id="rejectPayslipTitle" class="text-[1.2rem] font-bold">Reject payslip</h2>
                <button type="button" @click="rejecting = false" aria-label="Close rejection dialog"
                    class="h-9 w-9 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
            </div>
            <p class="mb-3 text-gray-500">Please provide a reason for rejecting this payslip.</p>

            <form method="POST" :action="slip?.action_url" class="grid gap-4">
                @csrf
                <input type="hidden" name="decision" value="reject">
                <label class="grid gap-1.5 text-sm font-semibold text-gray-700">
                    Reason
                    <textarea name="reason" rows="4" required maxlength="1000"
                        placeholder="Explain why you are rejecting this payslip"
                        class="rounded-md border border-gray-300 px-3 py-2.5 font-normal text-gray-900 focus:border-[#b22222] focus:outline-none focus:ring-1 focus:ring-[#b22222]"></textarea>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="rejecting = false"
                        class="min-h-[38px] cursor-pointer rounded-md border border-gray-300 bg-white px-4 py-2 font-bold text-gray-700 transition hover:bg-gray-100">Cancel</button>
                    <button type="submit"
                        class="min-h-[38px] cursor-pointer rounded-md border border-[#b22222] bg-[#b22222] px-4 py-2 font-bold text-white transition hover:bg-white hover:text-[#b22222]">
                        Submit rejection
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
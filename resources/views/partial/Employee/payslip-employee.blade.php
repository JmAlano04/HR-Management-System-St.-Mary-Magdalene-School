<x-slot name="header">
    <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-4xl font-extrabold leading-tight sm:ml-[10px] md:ml-[20px] lg:ml-[80px] text-red-700">Payslip</h1>
</x-slot>

@auth
    <x-slot name="title">
        @if (auth()->user()->hasRole('Employee'))
            Employee Payslip
       @endif
    </x-slot>
    @endauth
<div class="px-4 pb-12 sm:px-6 lg:px-10"
     x-data="{
        months: @js($months),
        month: null,
        slip: null,
        peso(v) { return '₱' + Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        get folder() { return this.months.find(m => m.key === this.month) || null; },
        openById(id) {
            for (const m of this.months) {
                const p = m.payslips.find(p => p.id === id);
                if (p) { this.month = m.key; this.slip = p; return; }
            }
        },
        close() { this.slip = null; },
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
                    <p class="mt-2 text-gray-500">Open a month to view your payslips.</p>
                </div>
            </div>

            @if ($months->isEmpty())
                <div class="grid place-items-center gap-2 rounded-[14px] border border-dashed border-gray-300 px-4 py-10 text-center text-gray-500">
                    <i class="fa-regular fa-folder-open text-2xl" aria-hidden="true"></i>
                    <span>Your released payslips will appear here after Accounting sends them.</span>
                </div>
            @else
                {{-- Month folders --}}
                <div x-show="!month" class="grid grid-cols-1 gap-3 sm:grid-cols-[repeat(auto-fit,minmax(280px,1fr))]">
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
                                        <strong class="text-sm text-gray-900" x-text="p.cutoff_label || p.cutoff"></strong>
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
            class="relative my-auto w-full max-w-[700px] rounded-[24px] bg-white px-2.5 pb-2.5 pt-1.5 shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
            <button type="button" @click="slip = null" aria-label="Close payslip"
                class="absolute right-3 top-3 h-8 w-8 cursor-pointer rounded-full text-xl leading-none text-gray-500 transition hover:bg-[#fef2f2] hover:text-[#b22222]">&times;</button>

            <template x-if="slip">
                <div class="bg-white">
                    <div class="flex flex-col items-center gap-0.5 px-3 pb-3 pt-2 text-center">
                        <strong id="payslipTitle" class="text-[clamp(1.35rem,2vw,2rem)] font-extrabold leading-tight text-gray-900" x-text="slip.employee.name"></strong>
                        <span class="text-[0.8rem] text-gray-800" x-text="'ID: ' + slip.employee.number"></span>
                        <small class="text-[0.8rem] text-gray-500"
                            x-text="slip.employee.department + ' | ' + slip.employee.position + ' | ' + (slip.step_name || '—')"></small>
                    </div>

                    <div class="grid grid-cols-2 border-y border-[#ecebea] sm:grid-cols-5">
                        <template x-for="(m, k) in [
                            ['Date', slip.cutoff_label || slip.cutoff],
                            ['Days worked', Number(slip.days)],
                            ['Hours worked', Number(slip.hours)],
                            ['Late (mins)', Number(slip.late_min)],
                            ['Monthly wage', peso((Number(slip.daily_wage) || 0) * 26)]
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
                                <strong class="min-w-[110px] text-center text-gray-900" x-text="peso(slip.basic_pay)"></strong>
                            </div>
                            <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.8rem] text-gray-500">
                                <span>Daily rate</span>
                                <strong class="min-w-[110px] text-center text-gray-900" x-text="peso(slip.daily_wage)"></strong>
                            </div>
                            <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.8rem] text-gray-500">
                                <span>Honorarium</span>
                                <strong class="min-w-[110px] text-center text-gray-900" x-text="Number(slip.honorarium) > 0 ? peso(slip.honorarium) : '-'"></strong>
                            </div>
                            <div class="mt-2 flex min-h-[28px] items-center justify-between gap-3 text-[0.8rem] font-extrabold text-gray-900">
                                <span>Gross earnings</span>
                                <strong class="min-w-[110px] text-center text-[#b22222]" x-text="peso(slip.gross_pay)"></strong>
                            </div>
                        </section>

                        <section class="min-w-0 border-t border-[#ecebea] p-2.5 sm:border-l">
                            <h3 class="mb-2 text-[0.72rem] font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Deductions</h3>
                            <template x-for="d in slip.deductions" :key="d[0]">
                                <div class="flex min-h-[28px] items-center justify-between gap-3 border-b border-[#ecebea] text-[0.8rem] text-gray-500">
                                    <span x-text="d[0]"></span>
                                    <strong class="min-w-[110px] text-center text-gray-900" x-text="peso(d[1])"></strong>
                                </div>
                            </template>
                            <div class="mt-2 flex min-h-[28px] items-center justify-between gap-3 text-[0.8rem] font-extrabold text-gray-900">
                                <span>Total deductions</span>
                                <strong class="min-w-[110px] text-center text-[#b22222]" x-text="'- ' + peso(slip.total_deductions)"></strong>
                            </div>
                        </section>
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t-2 border-[#b22222] bg-[#fef2f2] px-4 py-2.5 font-black tracking-[0.08em] text-[#b22222]">
                        <span>NET PAY</span>
                        <strong class="text-base tracking-normal" x-text="peso(slip.net_pay)"></strong>
                    </div>

                </div>
            </template>
        </section>
    </div>
</div>
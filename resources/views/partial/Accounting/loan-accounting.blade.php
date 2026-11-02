{{-- resources/views/accounting-loans/index.blade.php --}}
{{-- Wrap in your own layout (<x-app-layout> etc.) the same way your other pages do. --}}

<x-slot name="title">Financial Loan Management</x-slot>

<x-slot name="header">
    <h1 class="text-2xl font-extrabold leading-none ml-[80px] tracking-tight text-[#b22222] sm:text-4xl">Financial Loan Management</h1>
</x-slot>

<div
    class="grid gap-6 px-4 pb-12 sm:px-6 lg:px-10"
    x-data="loanReview(@js([
        'decideUrl' => route('accounting-loans.decide', '__ID__'),
        'loans'     => $details,
        'reopen'    => $errors->has('reason'),
        'loanId'    => old('loan_id'),
        'reason'    => old('reason', ''),
    ]))"
    @keydown.escape.window="closeTop()"
>

    {{-- ===================== 1. Approved loan balances ===================== --}}
    <section aria-labelledby="loanBalancesTitle"
        class="grid gap-[18px] rounded-[18px] border border-[#e8ebf0] bg-white p-[clamp(20px,3vw,30px)] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 id="loanBalancesTitle" class="text-[clamp(1.4rem,2.4vw,1.9rem)] font-bold tracking-tight text-[#182230]">Approved loan balances</h2>
                <p class="mt-2 max-w-3xl text-[0.92rem] text-[#7b8494]">
                    SSS, HMDF, PERAA/CEAP, and Cash Advance deductions reduce balances after the employee receives the payslip.
                    Other types stay unchanged when no matching loan deduction is tracked.
                </p>
            </div>
            <div class="grid min-w-[180px] flex-none gap-[5px] rounded-xl border border-[#f0d5d5] bg-[#fff7f7] px-4 py-3" aria-live="polite">
                <span class="text-[0.75rem] font-bold text-[#7b8494]">Total outstanding</span>
                <strong class="text-[1.2rem] text-[#9b2424]">₱{{ number_format($totalOutstanding, 2) }}</strong>
            </div>
        </div>

        @if ($balances->isEmpty())
            <p class="rounded-xl border border-dashed border-[#d7dde5] px-4 py-6 text-center text-[#778191]">
                No fully approved employee loans to display.
            </p>
        @else
            <div class="overflow-x-auto rounded-xl border border-[#e7eaf0]">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr>
                            @foreach (['Employee', 'Loan type', 'Approved amount', 'Paid via payslips', 'Outstanding'] as $col)
                                <th scope="col" class="whitespace-nowrap border-b border-[#edf0f4] bg-[#f8fafc] px-[15px] py-[13px] text-[0.72rem] font-extrabold uppercase tracking-[0.04em] text-[#667085]">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($balances as $row)
                            <tr class="border-b border-[#edf0f4] last:border-b-0">
                                <td class="min-w-[180px] px-[15px] py-[13px] text-[0.84rem] text-[#344054]">
                                    <strong class="block">{{ $row['loan']->employee->name }}</strong>
                                    <small class="mt-1 block text-[0.74rem] text-[#87909e]">{{ $row['loan']->employee->employee_id ?? 'ID unavailable' }}</small>
                                </td>
                                <td class="whitespace-nowrap px-[15px] py-[13px] text-[0.84rem] text-[#344054]">{{ $row['loan']->loan_type ?: 'Not provided' }}</td>
                                <td class="whitespace-nowrap px-[15px] py-[13px] text-[0.84rem] text-[#344054]">₱{{ number_format($row['principal'], 2) }}</td>
                                <td class="whitespace-nowrap px-[15px] py-[13px] text-[0.84rem] text-[#344054]">₱{{ number_format($row['repaid'], 2) }}</td>
                                <td class="whitespace-nowrap px-[15px] py-[13px] text-[0.84rem]"><strong class="text-[#9b2424]">₱{{ number_format($row['outstanding'], 2) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- ===================== 2. HR-approved loan requests ===================== --}}
    <section aria-labelledby="loanQueueTitle"
        class="grid gap-[18px] rounded-[18px] border border-[#e8ebf0] bg-white p-[clamp(20px,3vw,30px)] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 id="loanQueueTitle" class="text-[clamp(1.4rem,2.4vw,1.9rem)] font-bold tracking-tight text-[#182230]">HR-approved loan requests</h2>
                <p class="mt-2 text-[0.92rem] text-[#7b8494]">Only requests approved by HR can be reviewed by Accounting.</p>
            </div>
            <span class="whitespace-nowrap rounded-full border border-[#f0d5d5] bg-[#fff6f6] px-3 py-[9px] text-[0.78rem] font-extrabold text-[#9b2424]">
                {{ $awaiting }} awaiting Accounting
            </span>
        </div>

        {{-- Flash / errors --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->has('loan') || $errors->has('decision'))
            <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                {{ $errors->first('loan') ?: $errors->first('decision') }}
            </div>
        @endif

        <div class="grid gap-3" aria-live="polite">
            @forelse ($loans as $loan)
                <article
                    tabindex="0" role="button"
                    aria-label="View loan request from {{ $loan->employee?->name ?? 'Employee' }}"
                    @click="openDetails({{ $loan->id }})"
                    @keydown.enter.prevent="openDetails({{ $loan->id }})"
                    @keydown.space.prevent="openDetails({{ $loan->id }})"
                    class="grid cursor-pointer gap-[18px] rounded-[13px] border border-[#e7eaf0] p-[19px] transition hover:border-[#e2b9b9] hover:bg-[#fffafa] focus:outline-none focus-visible:ring-[3px] focus-visible:ring-[#b22222]/20 sm:grid-cols-[minmax(0,1fr)_auto]">

                    <div>
                        <h3 class="text-base font-bold text-[#202a3a]">{{ $loan->employee?->name ?? 'Employee' }}</h3>
                        <span class="mt-[5px] inline-block text-[0.78rem] text-[#7b8494]">{{ $loan->employee?->employee_id ?? 'ID unavailable' }}</span>
                        <p class="mt-3 text-[0.78rem] text-[#9aa2b0]">Select to view request details</p>
                    </div>

                    <div class="flex flex-wrap items-start gap-2 sm:justify-end" aria-label="Approval decisions">
                        @foreach ($loan->stageChips() as $chip)
                            @php
                                $dot = match ($chip['status']) {
                                    'approved' => 'bg-[#258653]',
                                    'rejected' => 'bg-[#c33b32]',
                                    'pending'  => 'bg-[#d29a21]',
                                    default    => 'bg-[#a0a6b1]',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-[7px] whitespace-nowrap rounded-full border border-dashed border-[#e0e3e9] px-2.5 py-[7px] text-[0.78rem] font-bold leading-tight text-[#687080]">
                                <span class="h-2 w-2 flex-none rounded-full {{ $dot }}"></span>
                                {{ $chip['label'] }}: {{ $chip['text'] }}
                            </span>
                        @endforeach
                    </div>
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-[#d7dde5] px-4 py-[26px] text-center text-[#778191]">
                    No employee loan requests have been submitted yet.
                </p>
            @endforelse
        </div>

        @if ($loans->hasPages())
            <div>{{ $loans->links() }}</div>
        @endif
    </section>

    {{-- ===================== Request details modal ===================== --}}
    <div x-show="detailId" x-cloak x-transition.opacity @click.self="detailId = null"
        class="fixed inset-0 z-[60] grid place-items-center overflow-y-auto bg-[rgba(20,27,39,0.55)] p-4 backdrop-blur-[2px]">
        <section role="dialog" aria-modal="true" aria-labelledby="loanDetailsTitle"
            class="relative w-[min(860px,calc(100vw-32px))] rounded-2xl border border-[#e6e9ef] bg-white shadow-[0_24px_70px_rgba(15,23,42,0.25)]">
            <template x-if="detail">
                <div class="grid gap-[18px] p-[clamp(22px,5vw,30px)]">
                    <button type="button" @click="detailId = null" aria-label="Close request details"
                        class="absolute right-[18px] top-4 grid h-9 w-9 place-items-center rounded-full text-2xl leading-none text-[#667085] hover:bg-[#f2f4f7]">&times;</button>

                    <p class="text-[0.72rem] font-extrabold uppercase tracking-[0.12em] text-[#b22222]">Loan request</p>
                    <h2 id="loanDetailsTitle" class="-mt-3 mr-11 text-[1.55rem] font-bold tracking-tight text-[#182230]">Request details</h2>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid content-start gap-4">
                            <div class="grid gap-[7px]"><small class="text-[0.82rem] text-[#87909e]">Employee</small><strong class="text-base text-[#344054]" x-text="detail.name"></strong></div>
                            <div class="grid gap-[7px]"><small class="text-[0.82rem] text-[#87909e]">Employee number</small><strong class="text-base text-[#344054]" x-text="detail.number"></strong></div>
                            <div class="grid gap-[7px]"><small class="text-[0.82rem] text-[#87909e]">Overall status</small><strong class="text-base text-[#b22222]" x-text="detail.status"></strong></div>
                        </div>
                        <div class="grid content-start gap-4">
                            <div class="grid gap-[7px]"><small class="text-[0.82rem] text-[#87909e]">Submitted</small><strong class="text-base text-[#344054]" x-text="detail.submitted"></strong></div>
                            <div class="grid gap-[7px]"><small class="text-[0.82rem] text-[#87909e]">Loan type</small><strong class="text-base text-[#344054]" x-text="detail.type"></strong></div>
                            <div class="grid gap-[7px]"><small class="text-[0.82rem] text-[#87909e]">Amount requested</small><strong class="text-base text-[#344054]" x-text="detail.amount"></strong></div>
                        </div>

                        <div class="rounded-lg bg-[#f7f8fa] px-[18px] py-4 sm:col-span-2">
                            <small class="text-[0.82rem] text-[#87909e]">Reason</small>
                            <p class="mt-1.5 whitespace-pre-wrap break-words text-[0.9rem] leading-[1.55] text-[#475467]" x-text="detail.reason"></p>
                        </div>

                        <div x-show="detail.rejection" class="rounded-lg bg-[#fff5f5] px-[18px] py-4 sm:col-span-2">
                            <small class="text-[0.82rem] text-[#87909e]">Rejection reason</small>
                            <p class="mt-1.5 whitespace-pre-wrap break-words text-[0.9rem] leading-[1.55] text-[#842020]" x-text="detail.rejection"></p>
                        </div>
                    </div>

                    {{-- Actions: only while Accounting can still decide --}}
                    <div x-show="detail.canReview" class="flex gap-3">
                        <form method="POST" :action="action(detailId)" class="contents">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="approved">
                            <button type="submit"
                                class="grid min-h-[42px] flex-1 cursor-pointer place-items-center rounded-[9px] bg-[#16803c] px-3 text-[0.84rem] font-extrabold text-white hover:bg-[#126b32]">Approve</button>
                        </form>
                        <button type="button" @click="openReject(detailId)"
                            class="grid min-h-[42px] flex-1 cursor-pointer place-items-center rounded-[9px] border border-[#c91f28] bg-[#c91f28] px-3 text-[0.84rem] font-extrabold text-white hover:border-[#aa1921] hover:bg-[#aa1921]">Reject</button>
                    </div>
                </div>
            </template>
        </section>
    </div>

    {{-- ===================== Rejection modal ===================== --}}
    <div x-show="rejectId" x-cloak x-transition.opacity @click.self="rejectId = null"
        class="fixed inset-0 z-[70] grid place-items-center bg-[rgba(20,27,39,0.55)] p-4">
        <section role="dialog" aria-modal="true" aria-labelledby="loanRejectionTitle"
            class="w-[min(520px,calc(100vw-32px))] rounded-2xl border border-[#e6e9ef] bg-white shadow-[0_24px_70px_rgba(15,23,42,0.25)]">
            <form method="POST" :action="action(rejectId)" class="grid gap-3 p-[clamp(22px,5vw,30px)]">
                @csrf
                @method('PATCH')
                <input type="hidden" name="decision" value="rejected">
                <input type="hidden" name="loan_id" :value="rejectId">

                <p class="-mb-1 text-[0.72rem] font-extrabold uppercase tracking-[0.12em] text-[#b22222]">Accounting decision</p>
                <h2 id="loanRejectionTitle" class="text-[1.4rem] font-bold tracking-tight text-[#182230]">Reason for rejection</h2>
                <p class="mb-1 text-[0.9rem] leading-relaxed text-[#667085]">Provide a reason so the employee understands why this request cannot proceed.</p>

                <label for="loanRejectionReason" class="text-[0.84rem] font-bold text-[#344054]">Rejection reason <span class="text-[#b22222]" aria-hidden="true">*</span></label>
                <textarea id="loanRejectionReason" name="reason" rows="4" maxlength="1000" required x-model="reason"
                    placeholder="Explain why this request is being rejected."
                    class="min-h-[116px] w-full resize-y rounded-[9px] border border-[#d5dbe3] p-3 text-[#273142] focus:border-[#b22222] focus:outline-none focus:ring-[3px] focus:ring-[#b22222]/15"></textarea>
                <small class="-mt-[7px] text-[0.76rem] text-[#818a99]">Maximum 1,000 characters.</small>
                <p class="text-xs font-semibold text-red-600">{{ $errors->first('reason') }}</p>

                <div class="mt-2 flex flex-col-reverse justify-end gap-[9px] sm:flex-row">
                    <button type="button" @click="rejectId = null"
                        class="min-h-[40px] cursor-pointer rounded-lg border border-[#d9dee6] bg-white px-3.5 text-[0.82rem] font-extrabold text-[#475467]">Cancel</button>
                    <button type="submit"
                        class="min-h-[40px] cursor-pointer rounded-lg bg-[#b22222] px-3.5 text-[0.82rem] font-extrabold text-white hover:bg-[#941b1b]">Confirm rejection</button>
                </div>
            </form>
        </section>
    </div>
</div>

<script>
    function loanReview(cfg) {
        return {
            loans: cfg.loans,
            detailId: null,
            // reopen the rejection modal with the typed text if the server rejected an empty reason
            rejectId: cfg.reopen ? Number(cfg.loanId) || null : null,
            reason: cfg.reopen ? cfg.reason : '',

            get detail() { return this.detailId ? this.loans[this.detailId] ?? null : null; },

            openDetails(id) { this.detailId = id; },

            openReject(id) {
                this.reason = '';
                this.rejectId = id;          // details modal stays open underneath, like the original dialogs
            },

            // Esc closes the top-most modal first
            closeTop() {
                if (this.rejectId) this.rejectId = null;
                else this.detailId = null;
            },

            action(id) { return cfg.decideUrl.replace('__ID__', id ?? ''); },
        };
    }
</script>
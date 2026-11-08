{{-- resources/views/accounting-loans/index.blade.php --}}
<<<<<<< HEAD

    <x-slot name="title">
       Accounting Financial Loan Management
    </x-slot>

    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none ml-[80px] tracking-tight text-[#b22222] sm:text-4xl">Financial Loan Management</h1>
    </x-slot>

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="{ detail: null, rejecting: false, reason: '', openLoan(loan) { this.detail = loan; this.rejecting = false; this.reason = ''; }, closeLoan() { this.detail = null; this.rejecting = false; this.reason = ''; } }"
        @keydown.escape.window="rejecting ? (rejecting = false) : closeLoan()"
    >
        {{-- Flash / errors --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        <section aria-labelledby="loanBalancesTitle" class="mb-5 rounded-2xl border border-[#e7eaf0] bg-white p-5 sm:p-6">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 id="loanBalancesTitle" class="text-xl font-extrabold text-[#202a3a]">Approved loan balances</h2>
                    <p class="mt-1 max-w-4xl text-sm text-[#7b8494]">Approved SSS, HDMF, PERAA/CEAP, and Cash Advance loans are reduced by repayments recorded from payslips.</p>
                </div>
                <div class="rounded-lg border border-[#f1caca] bg-[#fff7f7] px-3.5 py-2.5">
                    <small class="block text-xs font-semibold text-[#7b8494]">Total outstanding</small>
                    <strong class="text-lg font-extrabold text-[#b22222]">₱{{ number_format($totalOutstanding, 2) }}</strong>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-[#e7eaf0]">
                <table class="w-full min-w-[720px] border-collapse text-left text-xs">
                    <thead class="bg-[#f7f9fc] uppercase text-[#687385]">
                        <tr>
                            <th class="px-3 py-3 font-bold">Employee</th>
                            <th class="px-3 py-3 font-bold">Loan type</th>
                            <th class="px-3 py-3 font-bold">Approved amount</th>
                            <th class="px-3 py-3 font-bold">Paid via payslips</th>
                            <th class="px-3 py-3 font-bold">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e7eaf0]">
                        @forelse ($loanBalances as $balance)
                            <tr>
                                <td class="px-3 py-3">
                                    <strong class="block text-[#344054]">{{ $balance['employee'] }}</strong>
                                    <small class="text-[#87909e]">{{ $balance['employee_number'] }}</small>
                                </td>
                                <td class="px-3 py-3 text-[#344054]">{{ $balance['loan_type'] }}</td>
                                <td class="px-3 py-3 text-[#344054]">₱{{ number_format($balance['approved_amount'], 2) }}</td>
                                <td class="px-3 py-3 text-[#344054]">₱{{ number_format($balance['paid'], 2) }}</td>
                                <td class="px-3 py-3 font-bold text-[#b22222]">₱{{ number_format($balance['outstanding'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-5 text-center text-[#7b8494]">No fully approved loan balances yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section aria-labelledby="loanQueueTitle" class="rounded-2xl border border-[#e7eaf0] bg-white p-5 sm:p-6">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="loanQueueTitle" class="text-xl font-extrabold text-[#202a3a]">Loan requests</h2>
                    <p class="mt-1 text-sm text-[#7b8494]">Review requests after HR approval. Admin reviews next and provides final approval.</p>
                </div>
                <span class="rounded-full border border-[#f1caca] bg-[#fff7f7] px-3 py-1.5 text-xs font-extrabold text-[#b22222]">{{ $awaitingAccounting }} awaiting Accounting</span>
            </div>

            <div class="grid gap-3" aria-live="polite">
                @forelse ($loans as $loan)
                    @php
                        $accountingStage = $loan->effectiveStatus('accounting');
                        $adminStage = $loan->effectiveStatus('admin');
                        $loanDetails = [
                            'id' => $loan->id,
                            'employee' => $loan->employee?->name ?? 'Employee',
                            'employeeNumber' => $loan->employee?->employee_id ?? 'ID unavailable',
                            'loanType' => $loan->loan_type ?: 'Not provided',
                            'amount' => '₱' . number_format((float) $loan->amount, 2),
                            'submitted' => $loan->requested_at?->format('M j, Y, g:i A') ?? 'Date unavailable',
                            'reason' => $loan->reason ?: 'No purpose provided.',
                            'status' => $loan->gate_message,
                            'hrStatus' => $loan->hr_status,
                            'accountingStatus' => $accountingStage,
                            'adminStatus' => $adminStage,
                            'rejectionReason' => $loan->hr_status === 'rejected'
                                ? $loan->hr_rejection_reason
                                : ($loan->accounting_status === 'rejected' ? $loan->accounting_rejection_reason : $loan->admin_rejection_reason),
                            'canReview' => $loan->canReviewAs('accounting'),
                            'url' => route('loans.decide', $loan),
                        ];
                        $stageClass = fn (string $status) => match ($status) {
                            'approved' => 'border-green-200 bg-green-50 text-green-800',
                            'rejected' => 'border-red-200 bg-red-50 text-red-800',
                            'pending' => 'border-amber-200 bg-amber-50 text-amber-800',
                            default => 'border-gray-200 bg-gray-50 text-gray-500',
                        };
                    @endphp
                    <article tabindex="0" role="button"
                        @click="openLoan(JSON.parse($event.currentTarget.dataset.loan))"
                        @keydown.enter.prevent="openLoan(JSON.parse($event.currentTarget.dataset.loan))"
                        @keydown.space.prevent="openLoan(JSON.parse($event.currentTarget.dataset.loan))"
                        data-loan="{{ json_encode($loanDetails, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                        class="grid cursor-pointer items-center gap-4 rounded-xl border border-[#e7eaf0] bg-white p-4 transition hover:border-[#d9a3a3] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#b22222] md:grid-cols-[minmax(0,1fr)_auto]">
                        <div class="min-w-0">
                            <strong class="block text-sm text-[#202a3a]">{{ $loanDetails['employee'] }}</strong>
                            <small class="text-[#7b8494]">{{ $loanDetails['employeeNumber'] }}</small>
                            <p class="mt-2 text-xs text-[#7b8494]">{{ $loan->loan_type ?: 'Not provided' }} · ₱{{ number_format((float) $loan->amount, 2) }} · {{ $loan->requested_at?->format('M j, Y, g:i A') ?? 'Date unavailable' }}</p>
                            <p class="mt-2 truncate text-xs text-[#596273]">{{ $loan->reason ?: 'No purpose provided.' }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 md:justify-end">
                            @foreach ([['HR', $loan->hr_status], ['Accounting', $accountingStage], ['Admin', $adminStage]] as [$stageName, $stageStatus])
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-dashed px-2.5 py-1.5 text-[0.68rem] font-bold {{ $stageClass($stageStatus) }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $stageStatus === 'approved' ? 'bg-green-600' : ($stageStatus === 'rejected' ? 'bg-red-600' : ($stageStatus === 'pending' ? 'bg-amber-500' : 'bg-gray-400')) }}"></span>
                                    {{ $stageName }}: {{ $stageStatus === 'locked' ? 'Waiting' : ($stageStatus === 'blocked' ? 'Not proceeding' : ucfirst($stageStatus)) }}
                                </span>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-[#d7dde5] px-4 py-8 text-center text-[#778191]">No employee loan requests have been submitted yet.</p>
                @endforelse
            </div>

            @if ($loans->hasPages())
                <div class="mt-4">{{ $loans->links() }}</div>
            @endif
        </section>

        {{-- Request details and Accounting decision modal --}}
        <div x-show="detail" x-cloak x-transition.opacity @click.self="closeLoan()"
            class="fixed inset-0 z-[70] grid place-items-center bg-[rgba(20,27,39,0.55)] p-4">
            <section role="dialog" aria-modal="true" aria-labelledby="loanDetailsTitle"
                class="relative max-h-[90vh] w-[min(700px,calc(100vw-32px))] overflow-y-auto rounded-2xl bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.25)] sm:p-8">
                <button type="button" @click="closeLoan()" aria-label="Close loan request details"
                    class="absolute right-4 top-4 grid h-9 w-9 place-items-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
                <p class="text-xs font-extrabold uppercase tracking-[0.12em] text-[#b22222]">Loan request</p>
                <h2 id="loanDetailsTitle" class="mt-1 pr-10 text-2xl font-bold text-[#202a3a]">Request details</h2>

                <template x-if="detail">
                    <div>
                        <dl class="mt-6 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                            <div><dt class="text-xs text-[#87909e]">Employee</dt><dd class="mt-1 font-bold text-[#344054]" x-text="detail.employee"></dd></div>
                            <div><dt class="text-xs text-[#87909e]">Submitted</dt><dd class="mt-1 font-bold text-[#344054]" x-text="detail.submitted"></dd></div>
                            <div><dt class="text-xs text-[#87909e]">Employee number</dt><dd class="mt-1 font-bold text-[#344054]" x-text="detail.employeeNumber"></dd></div>
                            <div><dt class="text-xs text-[#87909e]">Loan type</dt><dd class="mt-1 font-bold text-[#344054]" x-text="detail.loanType"></dd></div>
                            <div><dt class="text-xs text-[#87909e]">Overall status</dt><dd class="mt-1 font-bold text-[#b22222]" x-text="detail.status"></dd></div>
                            <div><dt class="text-xs text-[#87909e]">Amount requested</dt><dd class="mt-1 font-bold text-[#344054]" x-text="detail.amount"></dd></div>
                        </dl>
                        <div class="mt-5 grid gap-2 sm:grid-cols-3">
                            <template x-for="(status, stage) in { HR: detail.hrStatus, Accounting: detail.accountingStatus, Admin: detail.adminStatus }" :key="stage">
                                <div class="rounded-lg border border-[#e7eaf0] px-3 py-2 text-sm">
                                    <span class="text-[#7b8494]" x-text="stage"></span>
                                    <strong class="ml-1 text-[#344054]" x-text="status === 'locked' ? 'Waiting' : (status === 'blocked' ? 'Not proceeding' : status.charAt(0).toUpperCase() + status.slice(1))"></strong>
                                </div>
                            </template>
                        </div>
                        <div class="mt-5 rounded-xl bg-[#f7f8fa] p-4">
                            <p class="text-xs text-[#87909e]">Reason</p>
                            <p class="mt-1 whitespace-pre-line break-words text-sm text-[#344054]" x-text="detail.reason"></p>
                        </div>
                        <template x-if="detail.rejectionReason">
                            <p class="mt-3 rounded-lg border-l-4 border-red-600 bg-red-50 px-4 py-3 text-sm text-red-800">
                                <strong>Rejection reason:</strong> <span x-text="detail.rejectionReason"></span>
                            </p>
                        </template>

                        <div x-show="detail.canReview && !rejecting" class="mt-6 flex flex-wrap items-center justify-between gap-2">
                            <form method="POST" :action="detail.url">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" value="approved">
                                <button type="submit" class="min-h-[40px] rounded-lg bg-[#16803c] px-4 text-sm font-bold text-white hover:bg-[#126b32]">Approve and forward to Admin</button>
                            </form>
                            <button type="button" @click="rejecting = true"
                                class="min-h-[40px] rounded-lg bg-[#b22222] px-4 text-sm font-bold text-white hover:bg-[#941b1b]">Reject</button>
                        </div>
                    </div>
                </template>
            </section>
        </div>

        <template x-teleport="body">
            <div x-show="detail && detail.canReview && rejecting" x-cloak x-transition.opacity
                @click.self="rejecting = false; reason = ''"
                class="fixed inset-0 z-[80] grid place-items-center bg-[rgba(20,27,39,0.65)] p-4">
                <template x-if="detail">
                    <section role="dialog" aria-modal="true" aria-labelledby="accountingLoanRejectionTitle"
                        class="w-[min(480px,calc(100vw-32px))] rounded-2xl bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.3)] sm:p-8">
                        <h2 id="accountingLoanRejectionTitle" class="text-xl font-bold text-[#202a3a]">Reject loan request</h2>
                        <p class="mb-5 mt-2 text-sm text-[#7b8494]">Please provide a reason for rejecting this request.</p>
                        <form method="POST" :action="detail.url" class="grid gap-3">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="rejected">
                            <input type="hidden" name="loan_id" :value="detail.id">
                            <label for="accountingLoanRejectionReason" class="text-sm font-bold text-[#344054]">
                                Rejection reason <span class="text-[#b22222]">*</span>
                            </label>
                            <textarea id="accountingLoanRejectionReason" name="reason" rows="4" maxlength="1000" required x-model="reason"
                                placeholder="Explain why this request is being rejected."
                                class="w-full resize-y rounded-lg border border-[#d5dbe3] p-3 text-sm text-[#273142] focus:border-[#b22222] focus:outline-none focus:ring-2 focus:ring-[#b22222]/15"></textarea>
                            <small class="text-xs text-[#818a99]">Maximum 1,000 characters.</small>
                            <div class="mt-2 flex justify-end gap-2">
                                <button type="button" @click="rejecting = false; reason = ''"
                                    class="min-h-[40px] rounded-lg border border-gray-200 px-4 text-sm font-bold text-gray-600 hover:bg-gray-50">Cancel</button>
                                <button type="submit"
                                    class="min-h-[40px] rounded-lg bg-[#b22222] px-4 text-sm font-bold text-white hover:bg-[#941b1b]">Confirm rejection</button>
                            </div>
                        </form>
                    </section>
                </template>
            </div>
        </template>

    </div>
=======
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

{{-- resources/views/accounting-loans/index.blade.php --}}

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

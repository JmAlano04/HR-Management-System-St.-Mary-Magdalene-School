{{-- resources/views/accounting-loans/index.blade.php --}}

    <x-slot name="title">
        Financial Loan Management
    </x-slot>

    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none ml-[80px] tracking-tight text-[#b22222] sm:text-4xl">Financial Loan Management</h1>
    </x-slot>

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="loanReview(@js([
            'decideUrl' => route('accounting-loans.decide', '__ID__'),
            'reopen'    => $errors->has('reason'),
            'loanId'    => old('loan_id'),
            'reason'    => old('reason', ''),
        ]))"
        @keydown.escape.window="rejectId = null"
    >
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
                    @php
                        $canReview = $loan->hr_status === 'approved' && $loan->accounting_status === 'pending';

                        $stateMessage = match (true) {
                            $loan->hr_status === 'rejected'            => 'Stopped after HR rejection',
                            $loan->hr_status !== 'approved'            => 'Waiting for HR review',
                            $loan->accounting_status === 'approved'    => 'Forwarded to Admin',
                            $loan->accounting_status === 'rejected'    => 'Rejected by Accounting',
                            default                                    => 'Awaiting Accounting review',
                        };

                        // colour of the status pill
                        $pill = match (true) {
                            $loan->hr_status !== 'approved'            => 'bg-[#f0f2f5] text-[#667085]',
                            $loan->accounting_status === 'approved'    => 'bg-[#edf8f1] text-[#177245]',
                            $loan->accounting_status === 'rejected'    => 'bg-[#fff0f0] text-[#b42318]',
                            default                                    => 'bg-[#fff7e8] text-[#946200]',
                        };

                        $rejectionReason = $loan->hr_status === 'rejected'
                            ? $loan->hr_rejection_reason
                            : ($loan->accounting_status === 'rejected' ? $loan->accounting_rejection_reason : null);
                    @endphp

                    <article class="grid gap-[18px] rounded-[13px] border border-[#e7eaf0] p-[19px] md:grid-cols-[minmax(0,1fr)_auto]">
                        <div>
                            <div class="flex flex-col items-start justify-between gap-3.5 sm:flex-row">
                                <div>
                                    <h3 class="text-base font-bold text-[#202a3a]">{{ $loan->employee?->name ?? 'Employee' }}</h3>
                                    <span class="mt-[5px] inline-block text-[0.78rem] text-[#7b8494]">{{ $loan->employee?->employee_id ?? 'ID unavailable' }}</span>
                                </div>
                                <span class="whitespace-nowrap rounded-full px-[9px] py-1.5 text-[0.74rem] font-extrabold {{ $pill }}">{{ $stateMessage }}</span>
                            </div>

                            <div class="mt-[17px] flex flex-wrap gap-x-[26px] gap-y-2.5">
                                <span class="grid gap-1"><small class="text-[0.72rem] text-[#87909e]">Loan type</small><strong class="text-[0.84rem] text-[#344054]">{{ $loan->loan_type ?: 'Not provided' }}</strong></span>
                                <span class="grid gap-1"><small class="text-[0.72rem] text-[#87909e]">Amount requested</small><strong class="text-[0.84rem] text-[#344054]">₱{{ number_format((float) $loan->amount, 2) }}</strong></span>
                                <span class="grid gap-1"><small class="text-[0.72rem] text-[#87909e]">Submitted</small><strong class="text-[0.84rem] text-[#344054]">{{ $loan->requested_at ? \Illuminate\Support\Carbon::parse($loan->requested_at)->format('M j, Y, g:i A') : 'Date unavailable' }}</strong></span>
                                <span class="grid gap-1"><small class="text-[0.72rem] text-[#87909e]">HR decision</small><strong class="text-[0.84rem] text-[#344054]">{{ ucfirst($loan->hr_status ?: 'pending') }}</strong></span>
                            </div>

                            <p class="mt-[15px] break-words text-[0.88rem] leading-relaxed text-[#596273]">{{ $loan->reason ?: 'No purpose provided.' }}</p>

                            @if ($rejectionReason)
                                <p class="mt-2.5 break-words rounded-r-[7px] border-l-[3px] border-[#b22222] bg-[#fff5f5] px-3 py-2.5 text-[0.84rem] leading-relaxed text-[#842020]">
                                    <strong>Rejection reason:</strong> {{ $rejectionReason }}
                                </p>
                            @endif
                        </div>

                        <div class="flex min-w-[170px] flex-row flex-wrap justify-center gap-2 md:flex-col md:flex-nowrap">
                            @if ($canReview)
                                <form method="POST" action="{{ route('accounting-loans.decide', $loan) }}" class="contents">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="decision" value="approved">
                                    <button type="submit"
                                        class="min-h-[38px] flex-1 cursor-pointer rounded-lg bg-[#b22222] px-3 text-[0.8rem] font-extrabold text-white hover:bg-[#941b1b] md:flex-none">Approve for Admin</button>
                                </form>
                                <button type="button" @click="openReject({{ $loan->id }})"
                                    class="min-h-[38px] flex-1 cursor-pointer rounded-lg border border-[#efc9c9] bg-white px-3 text-[0.8rem] font-extrabold text-[#a32020] hover:bg-[#fff4f4] md:flex-none">Reject</button>
                            @else
                                <span class="text-[0.8rem] leading-snug text-[#667085] md:text-right">{{ $stateMessage }}</span>
                            @endif
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

        {{-- ============ Rejection modal ============ --}}
        <div x-show="rejectId" x-cloak x-transition.opacity @click.self="rejectId = null"
            class="fixed inset-0 z-[70] grid place-items-center bg-[rgba(20,27,39,0.55)] p-4">
            <section role="dialog" aria-modal="true" aria-labelledby="loanRejectionTitle"
                class="w-[min(520px,calc(100vw-32px))] rounded-2xl border border-[#e6e9ef] bg-white shadow-[0_24px_70px_rgba(15,23,42,0.25)]">
                <form method="POST" :action="decideAction()" class="grid gap-3 p-[clamp(22px,5vw,30px)]">
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
                // reopen the modal with the typed text if the server rejected an empty reason
                rejectId: cfg.reopen ? Number(cfg.loanId) || null : null,
                reason: cfg.reopen ? cfg.reason : '',

                openReject(id) {
                    this.reason = '';
                    this.rejectId = id;
                },

                decideAction() {
                    return cfg.decideUrl.replace('__ID__', this.rejectId ?? '');
                },
            };
        }
    </script>

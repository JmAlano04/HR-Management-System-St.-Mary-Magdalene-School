{{-- resources/views/admin/loan-management/index.blade.php --}}
    {{-- Browser tab title --}}
    <x-slot name="title">
        Financial Loan Dashboard
    </x-slot>

    {{-- Page heading (shown in the layout's top bar) --}}
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none  ml-20 tracking-tight text-[#b22222] sm:text-4xl">Financial Loan Management</h1>
    </x-slot>

    @php
        // Look of the status badge
        $badge = fn (string $key) => match ($key) {
            'approved' => 'bg-[#edf8f1] text-[#177245]',
            'rejected' => 'bg-[#fff0f0] text-[#b42318]',
            'locked'   => 'bg-[#f0f2f5] text-[#667085]',
            default    => 'bg-[#fff7e8] text-[#946200]',
        };
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="loanReview()"
        @keydown.escape.window="rejectUrl = null"
    >
        <section aria-labelledby="loanQueueTitle"
            class="mt-2 max-w-[1100px] rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">

            {{-- Heading + awaiting counter --}}
            <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Loan requests</p>
                    <h2 id="loanQueueTitle" class="text-2xl font-bold text-gray-900">Accounting-approved loan requests</h2>
                    <p class="mt-2 text-gray-500">Admin review opens only after both HR and Accounting approve.</p>
                </div>

                <span class="whitespace-nowrap rounded-full border border-[#f0d5d5] bg-[#fff6f6] px-3 py-2 text-xs font-extrabold text-[#9b2424]" aria-live="polite">
                    {{ $awaitingAdmin }} awaiting Admin
                </span>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Loan request list --}}
            <div class="grid gap-3" aria-live="polite">
                @forelse ($loans as $loan)
                    <article class="grid gap-[18px] rounded-[14px] border border-[#ecebea] bg-white p-5 transition duration-200 hover:border-[#d9a5a5] hover:shadow-[0_10px_30px_rgba(15,23,42,0.05)] md:grid-cols-[minmax(0,1fr)_auto]">

                        <div class="min-w-0">
                            {{-- Employee + status --}}
                            <div class="flex flex-col items-start justify-between gap-3 sm:flex-row">
                                <div>
                                    <h3 class="text-base font-bold text-slate-800">{{ $loan->employee?->name ?? 'Employee' }}</h3>
                                    <span class="mt-1 inline-block text-xs text-gray-500">{{ $loan->employee?->employee_id ?? 'ID unavailable' }}</span>
                                </div>

                                <span class="whitespace-nowrap rounded-full px-2.5 py-1.5 text-xs font-extrabold {{ $badge($loan->badge_key) }}">
                                    {{ $loan->gate_message }}
                                </span>
                            </div>

                            {{-- Facts --}}
                            <div class="mt-4 flex flex-wrap gap-x-7 gap-y-3">
                                @foreach ([
                                    ['Loan type',           $loan->loan_type ?: 'Not provided'],
                                    ['Amount requested',    $loan->formatted_amount],
                                    ['Submitted',           $loan->requested_at?->format('M d, Y, h:i A') ?? 'Date unavailable'],
                                    ['HR decision',         ucfirst($loan->hr_status)],
                                    ['Accounting decision', $loan->accounting_status === 'pending' && $loan->hr_status !== 'approved' ? 'Waiting' : ucfirst($loan->accounting_status)],
                                ] as [$label, $value])
                                    <span class="grid gap-1">
                                        <small class="text-[0.72rem] text-gray-400">{{ $label }}</small>
                                        <strong class="text-[0.84rem] text-gray-700">{{ $value }}</strong>
                                    </span>
                                @endforeach
                            </div>

                            {{-- Purpose --}}
                            <p class="mt-4 break-words text-sm leading-relaxed text-gray-600">{{ $loan->reason ?: 'No purpose provided.' }}</p>

                            {{-- Rejection reason (from whichever stage rejected it) --}}
                            @if ($loan->rejection_reason_text)
                                <p class="mt-2.5 break-words rounded-r-[7px] border-l-[3px] border-[#b22222] bg-[#fff5f5] px-3 py-2.5 text-[0.84rem] leading-relaxed text-[#842020]">
                                    <strong>Rejection reason:</strong> {{ $loan->rejection_reason_text }}
                                </p>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex min-w-0 flex-row flex-wrap items-center justify-center gap-2 md:min-w-[170px] md:flex-col">
                            @if ($loan->can_review)
                                <form method="POST" action="{{ route('loans.decide', $loan) }}" class="m-0 w-full flex-1 md:flex-none"
                                    onsubmit="return confirm('Approve this loan request?');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="decision" value="approved">
                                    <button type="submit"
                                        class="min-h-[38px] w-full cursor-pointer rounded-lg bg-[#b22222] px-3 text-[0.8rem] font-extrabold text-white transition hover:bg-[#941b1b]">
                                        Approve request
                                    </button>
                                </form>

                                <button type="button" @click="openReject('{{ route('loans.decide', $loan) }}')"
                                    class="min-h-[38px] w-full flex-1 cursor-pointer rounded-lg border border-[#efc9c9] bg-white px-3 text-[0.8rem] font-extrabold text-[#a32020] transition hover:bg-[#fff4f4] md:flex-none">
                                    Reject
                                </button>
                            @else
                                <span class="text-[0.8rem] leading-snug text-gray-500 md:text-right">{{ $loan->gate_message }}</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-[14px] border border-dashed border-[#e5caca] p-[30px] text-center text-gray-500">
                        No employee loan requests have been submitted yet.
                    </div>
                @endforelse
            </div>

            @if ($loans->hasPages())
                <div class="mt-5">{{ $loans->links() }}</div>
            @endif
        </section>

        {{-- ============ Rejection reason modal ============ --}}
        <div x-show="rejectUrl" x-cloak x-transition.opacity class="fixed inset-0 z-[60] grid place-items-center p-5">
            <div class="absolute inset-0 bg-[rgba(17,24,39,0.55)]" @click="rejectUrl = null"></div>

            <section role="dialog" aria-modal="true" aria-labelledby="loanRejectionTitle"
                class="relative w-full max-w-[520px] rounded-[16px] bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.25)] sm:p-[30px]">

                <p class="mb-2 text-[0.72rem] font-extrabold uppercase tracking-[0.12em] text-[#b22222]">Admin decision</p>
                <h2 id="loanRejectionTitle" class="text-[1.4rem] font-bold tracking-tight text-slate-900">Reason for rejection</h2>
                <p class="mb-3 mt-2 text-sm leading-relaxed text-gray-500">
                    Provide a reason so the employee understands why this request is being rejected.
                </p>

                <form method="POST" :action="rejectUrl" class="grid gap-2.5">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="decision" value="rejected">

                    <label for="loanRejectionReason" class="text-[0.84rem] font-bold text-gray-700">
                        Rejection reason <span class="text-[#b22222]" aria-hidden="true">*</span>
                    </label>
                    <textarea id="loanRejectionReason" name="reason" rows="4" maxlength="1000" required
                        placeholder="Explain why this request is being rejected."
                        x-ref="reason"
                        x-effect="if (rejectUrl) $nextTick(() => { $refs.reason.value = ''; $refs.reason.focus(); })"
                        class="min-h-[116px] w-full resize-y rounded-[9px] border border-gray-300 p-3 text-gray-800 outline-none focus:border-[#b22222] focus:ring-2 focus:ring-[#b22222]/15"></textarea>
                    <small class="text-xs text-gray-400">Maximum 1,000 characters.</small>

                    <div class="mt-2 flex flex-col-reverse justify-end gap-2 sm:flex-row">
                        <button type="button" @click="rejectUrl = null"
                            class="min-h-[40px] cursor-pointer rounded-lg border border-gray-300 bg-white px-4 text-[0.82rem] font-extrabold text-gray-600 transition hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="min-h-[40px] cursor-pointer rounded-lg bg-[#b22222] px-4 text-[0.82rem] font-extrabold text-white transition hover:bg-[#941b1b]">
                            Confirm rejection
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        function loanReview() {
            return {
                rejectUrl: null,

                openReject(url) {
                    this.rejectUrl = url;
                },
            };
        }
    </script>


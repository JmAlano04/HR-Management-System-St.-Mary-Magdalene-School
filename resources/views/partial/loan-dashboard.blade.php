{{-- Needs: $loans, $tab, $month (from LoanController@index) --}}
  @auth
    <x-slot name="title">
        @if (auth()->user()->hasRole('Admin'))
            Admin Financial Loan Management
        @elseif (auth()->user()->hasRole('HR'))
            HR Financial Loan Management
        @elseif (auth()->user()->hasRole('Accounting'))
            Accounting Financial Loan Management
        @elseif (auth()->user()->hasRole('Employee'))
            Employee Financial Loan Management
        @elseif (auth()->user()->hasRole('Applicant'))
            Applicant Dashboard
        @else
            Dashboard
        @endif
    </x-slot>
@endauth
@php
    $badge = [
        'locked'   => 'bg-gray-100 text-gray-600',
        'pending'  => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
    ];
    $stage = \App\Models\LoanRequest::stageForUser(auth()->user());
@endphp

<div class="space-y-5 px-4 pb-12 sm:px-6 lg:px-10"
     x-data="staffLoanReview(@js([
         'decideUrl' => route('loans.decide', '__ID__'),
         'archiveUrl' => route('loans.archive', '__ID__'),
         'restoreUrl' => route('loans.restore', '__ID__'),
         'reopen' => $errors->has('reason'),
         'loanId' => old('loan_id'),
     ]))"
     @keydown.escape.window="rejecting ? cancelRejection() : closeLoan()">
    {{-- Flash + errors --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Loan request panel --}}
    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
      {{-- Overview --}}
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl">Employee loan requests</h2>
            <p class="mt-1 text-sm text-gray-500">Requests are reviewed in order by HR, Accounting, then Admin for final approval.</p>
        </div>
      </div>

      {{-- Tabs + month filter (filter shows on Archive tab only) --}}
      <div class="mt-6 border-b border-gray-100 pb-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="hrms-tablist">
                <a href="{{ route('loans.index', ['tab' => 'active']) }}"
                   aria-current="{{ $tab === 'active' ? 'page' : 'false' }}"
                   class="hrms-tab text-sm font-semibold">
                    Active
                </a>
                <a href="{{ route('loans.index', ['tab' => 'archive']) }}"
                   aria-current="{{ $tab === 'archive' ? 'page' : 'false' }}"
                   class="hrms-tab text-sm font-semibold">
                    Archive
                </a>
            </div>

            @if ($tab === 'archive')
                <form method="GET" action="{{ route('loans.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="archive">
                    <label for="month" class="text-sm font-medium text-gray-600">Month</label>
                    <input type="month" id="month" name="month" value="{{ $month }}"
                           class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200">
                    <button type="submit" class="rounded-lg bg-red-700 px-3 py-2 text-sm font-medium text-white transition hover:bg-red-800">Filter</button>
                    @if ($month)
                        <a href="{{ route('loans.index', ['tab' => 'archive']) }}"
                           class="text-sm font-medium text-gray-500 underline-offset-2 hover:underline">Clear</a>
                    @endif
                </form>
            @endif
        </div>
      </div>

      {{-- Request cards --}}
      <div class="grid gap-3" aria-live="polite">
        @forelse ($loans as $loan)
            @php
                $accountingStage = $loan->effectiveStatus('accounting');
                $adminStage = $loan->effectiveStatus('admin');
                $statusPill = fn (string $value) => match ($value) {
                    'approved' => 'border-green-200 bg-green-50 text-green-800',
                    'rejected' => 'border-red-200 bg-red-50 text-red-800',
                    'pending' => 'border-amber-200 bg-amber-50 text-amber-800',
                    default => 'border-gray-200 bg-gray-50 text-gray-500',
                };
                $stageText = fn (string $name, string $value) => $name.': '.match ($value) {
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'pending' => 'Pending',
                    'blocked' => 'Not proceeding',
                    default => 'Waiting',
                };
                $loanDetails = [
                    'id' => $loan->id,
                    'employee' => $loan->employee?->name ?? 'Employee',
                    'employeeNumber' => $loan->employee?->employee_id ?? 'ID unavailable',
                    'loanType' => $loan->loan_type ?: 'Not provided',
                    'amount' => $loan->formatted_amount,
                    'reason' => $loan->reason ?: 'No purpose provided.',
                    'submitted' => $loan->requested_at?->format('M j, Y, g:i A') ?? 'Date unavailable',
                    'status' => $loan->gate_message,
                    'rejectionReason' => $loan->rejection_reason_text,
                    'hrStatus' => $loan->hr_status,
                    'accountingStatus' => $accountingStage,
                    'adminStatus' => $adminStage,
                    'canReview' => $tab === 'active' && $loan->canReviewAs($stage),
                    'canArchive' => $tab === 'active' && $loan->can_archive,
                    'isArchived' => $tab === 'archive',
                    'archivedAt' => $loan->staff_archived_at?->format('M d, Y'),
                    'url' => route('loans.decide', $loan),
                    'archiveUrl' => route('loans.archive', $loan),
                    'restoreUrl' => route('loans.restore', $loan),
                ];
            @endphp
            <article
                tabindex="0" role="button"
                @click="openLoan(JSON.parse($event.currentTarget.dataset.loan))"
                @keydown.enter.prevent="openLoan(JSON.parse($event.currentTarget.dataset.loan))"
                @keydown.space.prevent="openLoan(JSON.parse($event.currentTarget.dataset.loan))"
                data-loan="{{ json_encode($loanDetails, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                class="grid cursor-pointer gap-4 rounded-[14px] border border-gray-200 bg-white p-5 transition hover:border-red-200 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700 md:grid-cols-[minmax(0,1fr)_auto]">
                <div class="min-w-0">
                    <h3 class="font-bold text-gray-900">{{ $loan->employee?->name ?? 'Employee' }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $loan->employee?->employee_id ?? 'ID unavailable' }}</p>
                    <p class="mt-3 text-sm text-gray-500">Select to view request details</p>
                </div>
                <div class="flex flex-wrap items-start gap-2 md:justify-end">
                    @foreach ([
                        ['HR', $loan->hr_status],
                        ['Accounting', $accountingStage],
                        ['Admin', $adminStage],
                    ] as [$stageName, $stageStatus])
                        <span class="inline-flex whitespace-nowrap items-center gap-1.5 rounded-full border border-dashed px-3 py-1.5 text-xs font-bold {{ $statusPill($stageStatus) }}">
                            <span class="h-2 w-2 rounded-full {{ $stageStatus === 'approved' ? 'bg-green-600' : ($stageStatus === 'rejected' ? 'bg-red-600' : ($stageStatus === 'pending' ? 'bg-amber-500' : 'bg-gray-400')) }}"></span>
                            {{ $stageText($stageName, $stageStatus) }}
                        </span>
                    @endforeach
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-10 text-center text-gray-500">
                @if ($tab === 'archive')
                    No archived loan requests{{ $month ? ' for ' . \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') : '' }}.
                @else
                    No loan requests.
                @endif
            </div>
        @endforelse
      </div>

      @if ($loans->hasPages())
          <div class="mt-4 border-t border-gray-100 pt-4">
              {{ $loans->links() }}
          </div>
      @endif
    </section>

    {{-- Request details modal --}}
    <template x-teleport="body">
    <div x-show="detail" x-cloak x-transition.opacity @click.self="closeLoan()"
         class="fixed inset-0 z-[70] grid place-items-center bg-[rgba(20,27,39,0.55)] p-4">
        <section role="dialog" aria-modal="true" aria-labelledby="loanDetailsTitle"
                 class="relative max-h-[90vh] w-[min(940px,calc(100vw-32px))] overflow-y-auto rounded-2xl bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.25)] sm:p-8">
            <button type="button" @click="closeLoan()" aria-label="Close loan request details"
                    class="absolute right-5 top-5 grid h-10 w-10 place-items-center rounded-full border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-900">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <p class="text-xs font-extrabold uppercase tracking-[0.12em] text-red-700">Loan request</p>
            <h2 id="loanDetailsTitle" class="mt-1 pr-12 text-2xl font-bold tracking-tight text-gray-900">Request details</h2>

            <template x-if="detail">
                <div>
                    <dl class="mt-6 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                        <div><dt class="text-sm text-gray-400">Amount requested</dt><dd class="mt-1 font-bold text-gray-800" x-text="detail.amount"></dd></div>
                        <div><dt class="text-sm text-gray-400">Employee</dt><dd class="mt-1 font-bold text-gray-800" x-text="detail.employee"></dd></div>
                        <div><dt class="text-sm text-gray-400">Submitted</dt><dd class="mt-1 font-bold text-gray-800" x-text="detail.submitted"></dd></div>
                        <div><dt class="text-sm text-gray-400">Employee number</dt><dd class="mt-1 font-bold text-gray-800" x-text="detail.employeeNumber"></dd></div>
                        <div><dt class="text-sm text-gray-400">Loan type</dt><dd class="mt-1 font-bold text-gray-800" x-text="detail.loanType"></dd></div>
                        <div><dt class="text-sm text-gray-400">Overall status</dt><dd class="mt-1 font-bold text-red-700" x-text="detail.status"></dd></div>
                    </dl>

                    <div class="mt-6 grid gap-2 sm:grid-cols-3">
                        <template x-for="(stage, name) in { HR: detail.hrStatus, Accounting: detail.accountingStatus, Admin: detail.adminStatus }" :key="name">
                            <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                <span class="text-gray-500" x-text="name"></span>
                                <strong class="ml-1 text-gray-800" x-text="stage === 'locked' ? 'Waiting' : stage.charAt(0).toUpperCase() + stage.slice(1)"></strong>
                            </div>
                        </template>
                    </div>

                    <div class="mt-6 rounded-xl bg-gray-50 p-5">
                        <p class="text-sm text-gray-400">Reason</p>
                        <p class="mt-1 whitespace-pre-line break-words text-gray-700" x-text="detail.reason"></p>
                    </div>
                    <template x-if="detail.rejectionReason">
                        <p class="mt-3 rounded-lg border-l-4 border-red-600 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <strong>Rejection reason:</strong> <span x-text="detail.rejectionReason"></span>
                        </p>
                    </template>

                    <div x-show="!rejecting" class="mt-6 flex flex-wrap items-center justify-between gap-3">
                        <template x-if="detail.canReview">
                            <div class="flex flex-1 flex-wrap items-center justify-between gap-3">
                                <form method="POST" :action="detail.url" class="m-0"
                                      onsubmit="return confirm('Approve this loan request?')">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="decision" value="approved">
                                    <button type="submit" class="min-w-[9rem] rounded-lg bg-green-600 px-6 py-3 font-bold text-white transition hover:bg-green-700">Approve</button>
                                </form>
                                <button type="button" @click="rejecting = true"
                                        class="min-w-[9rem] rounded-lg bg-red-600 px-6 py-3 font-bold text-white transition hover:bg-red-700">Reject</button>
                            </div>
                        </template>
                        <template x-if="detail.canArchive">
                            <form method="POST" :action="detail.archiveUrl" class="m-0"
                                  onsubmit="return confirm('Archive this loan request?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-lg bg-gray-700 px-5 py-3 font-bold text-white transition hover:bg-gray-800">Archive</button>
                            </form>
                        </template>
                        <template x-if="detail.isArchived">
                            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                                <p class="text-sm text-gray-500">Archived <span x-text="detail.archivedAt || 'date unavailable'"></span></p>
                                <form method="POST" :action="detail.restoreUrl" class="m-0"
                                      onsubmit="return confirm('Restore this loan request to Active?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-3 font-bold text-white transition hover:bg-blue-700">Restore</button>
                                </form>
                            </div>
                        </template>
                        <template x-if="!detail.canReview && !detail.canArchive && !detail.isArchived">
                            <p class="text-sm font-medium text-gray-500">No action is available for this request yet.</p>
                        </template>
                    </div>

                </div>
            </template>
        </section>
    </div>
    </template>
    <div x-show="detail && rejecting" x-cloak x-transition.opacity @click.self="cancelRejection()"
         class="fixed inset-0 z-[80] grid place-items-center bg-[rgba(20,27,39,0.65)] p-4">
        <template x-if="detail">
            <section role="dialog" aria-modal="true" aria-labelledby="loanRejectionTitle"
                     class="w-[min(480px,calc(100vw-32px))] rounded-2xl bg-white p-6 shadow-[0_24px_70px_rgba(15,23,42,0.3)] sm:p-8">
                <h2 id="loanRejectionTitle" class="text-xl font-bold text-gray-900">Reject loan request</h2>
                <p class="mb-5 mt-2 text-sm text-gray-500">Please provide a reason for rejecting this request.</p>
                <form method="POST" :action="detail.url" class="grid gap-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="decision" value="rejected">
                    <input type="hidden" name="loan_id" :value="detail.id">
                    <label for="loanRejectionReason" class="text-sm font-bold text-gray-800">
                        Rejection reason <span class="text-red-700">*</span>
                    </label>
                    <textarea id="loanRejectionReason" name="reason" rows="4" maxlength="1000" required x-model="reason"
                              placeholder="Explain why this request is being rejected."
                              class="w-full resize-y rounded-lg border border-gray-300 p-3 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200"></textarea>
                    <small class="text-xs text-gray-500">Maximum 1,000 characters.</small>
                    <div class="mt-2 flex justify-end gap-2">
                        <button type="button" @click="cancelRejection()"
                                class="min-h-[40px] rounded-lg border border-gray-200 px-4 text-sm font-bold text-gray-600 hover:bg-gray-50">Cancel</button>
                        <button type="submit"
                                class="min-h-[40px] rounded-lg bg-red-600 px-4 text-sm font-bold text-white hover:bg-red-700">Confirm rejection</button>
                    </div>
                </form>
            </section>
        </template>
    </div>
</div>

<script>
    function staffLoanReview(config) {
        return {
            detail: null,
            rejecting: false,
            reason: '',
            init() {
                if (!config.reopen || !config.loanId) return;
                const loanCard = [...this.$el.querySelectorAll('[data-loan]')]
                    .find((card) => Number(JSON.parse(card.dataset.loan).id) === Number(config.loanId));
                if (!loanCard) return;
                const loan = JSON.parse(loanCard.dataset.loan);
                this.detail = loan;
                this.rejecting = true;
                this.reason = @js(old('reason', ''));
            },
            openLoan(loan) {
                this.detail = loan;
                this.rejecting = false;
                this.reason = '';
            },
            closeLoan() {
                this.detail = null;
                this.rejecting = false;
                this.reason = '';
            },
            cancelRejection() {
                this.rejecting = false;
                this.reason = '';
            },
        };
    }
</script>
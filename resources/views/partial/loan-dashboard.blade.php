<<<<<<< HEAD
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

=======
{{-- resources/views/loans/index.blade.php  (one page for HR, Accounting and Admin) --}}
{{-- Wrap in your own layout (<x-app-layout> etc.) the same way your other pages do. --}}

<x-slot name="title">Financial Loan Management</x-slot>

<x-slot name="header">
    <h1 class="text-2xl font-extrabold leading-none ml-[80px] tracking-tight text-[#b22222] sm:text-4xl">Financial Loan Management</h1>
</x-slot>

<div
    class="grid gap-6 px-4 pb-12 sm:px-6 lg:px-10"
    x-data="loanReview(@js([
        'decideUrl' => route('loans.decide', '__ID__'),
        'loans'     => $details,
        'reopen'    => $errors->has('reason'),
        'loanId'    => old('loan_id'),
        'reason'    => old('reason', ''),
    ]))"
    @keydown.escape.window="closeTop()"
>

    @if ($config['showBalances'])
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
    @endif

    {{-- ===================== Loan requests (wording depends on role) ===================== --}}
    <section aria-labelledby="loanQueueTitle"
        class="grid gap-[18px] rounded-[18px] border border-[#e8ebf0] bg-white p-[clamp(20px,3vw,30px)] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 id="loanQueueTitle" class="text-[clamp(1.4rem,2.4vw,1.9rem)] font-bold tracking-tight text-[#182230]">{{ $config['heading'] }}</h2>
                <p class="mt-2 text-[0.92rem] text-[#7b8494]">{{ $config['subheading'] }}</p>
            </div>
            <span class="whitespace-nowrap rounded-full border border-[#f0d5d5] bg-[#fff6f6] px-3 py-[9px] text-[0.78rem] font-extrabold text-[#9b2424]">
                {{ $awaiting }} awaiting {{ $config['queueLabel'] }}
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

                    {{-- Actions: only while this role can still decide --}}
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                </div>
            </template>
        </section>
    </div>
<<<<<<< HEAD
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
=======

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

                <p class="-mb-1 text-[0.72rem] font-extrabold uppercase tracking-[0.12em] text-[#b22222]">{{ $config['decisionLabel'] }}</p>
                <h2 id="loanRejectionTitle" class="text-[1.4rem] font-bold tracking-tight text-[#182230]">Reason for rejection</h2>
                <p class="mb-1 text-[0.9rem] leading-relaxed text-[#667085]">{{ $config['rejectionHelp'] }}</p>

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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    </div>
</div>

<script>
<<<<<<< HEAD
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
=======
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        };
    }
</script>
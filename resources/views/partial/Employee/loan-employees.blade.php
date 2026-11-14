{{-- resources/views/employee/loan-request.blade.php --}}
  @auth
    <x-slot name="title">
        @if (auth()->user()->hasRole('Employee'))
            Employee Financial Loan Request
       @endif
    </x-slot>
    @endauth
       <x-slot name="header">
        <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-4xl font-extrabold leading-tight sm:ml-[10px] md:ml-[20px] lg:ml-[80px] text-red-700">Financial Loan Request</h1>
    </x-slot>

    @php
        $inputClass = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-900 transition focus:border-[#b22222] focus:outline-none focus:ring-2 focus:ring-red-700/10';
        $card = 'rounded-xl border border-gray-200 bg-white p-4 sm:p-5';

        // Approval-stage chip colours + labels
        $stageBadge = fn (string $status) => match ($status) {
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'pending'  => 'bg-amber-100 text-amber-800',
            default    => 'bg-gray-100 text-gray-500',   // locked / blocked
        };
        $stageLabel = fn (string $stage, string $status) => match ($status) {
            'locked'  => $stage === 'Accounting' ? 'Waiting for HR' : 'Waiting for Accounting',
            'blocked' => 'Not proceeding',
            default   => ucfirst($status),
        };
        $peso = fn ($n) => '₱' . number_format((float) $n, 2);
    @endphp

    <div class="px-4 pb-12 pt-2 sm:px-6 lg:px-10" x-data>

        @if (! $employee)
            <div class="rounded-[20px] bg-white p-10 text-center text-gray-500 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                <i class="fa-solid fa-link-slash mb-3 text-4xl text-gray-300"></i>
                <p class="font-semibold">Your account is not linked to an employee record yet.</p>
                <p class="mt-1 text-sm">Please contact HR.</p>
            </div>
        @else
            <section aria-label="Financial Loan Request" class="flex flex-col gap-4">

                {{-- Flash message --}}
                @if (session('status'))
                    <div role="status" class="rounded-2xl bg-green-50 px-5 py-3.5 text-sm font-semibold text-green-800">
                        <i class="fa-solid fa-circle-check mr-1.5"></i>{{ session('status') }}
                    </div>
                @endif

                {{-- My loan balances --}}
                <div class="{{ $card }}">
                    <h2 class="text-lg font-extrabold text-gray-900">My loan balances</h2>
                    <p class="mb-4 mt-1 text-sm text-gray-500">Outstanding approved loan balances after repayments on your payslips.</p>

                  @php $cashAdvanceBalance = $balances['byType']['Cash Advance'] ?? 0; @endphp
                  <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                      <span class="text-sm font-medium text-gray-600">Cash Advance</span>
                      <strong class="text-base font-bold text-gray-900">{{ $peso($cashAdvanceBalance) }}</strong>
                  </div>
                </div>

                {{-- Request form --}}
                <form method="POST" action="{{ route('loan.loan-employee.store') }}"
                    x-data="{ type: @js(old('loan_type', 'Cash Advance')), amount: @js(old('amount', '')), reason: @js(old('reason', '')), limit: {{ \App\Models\LoanRequest::CASH_ADVANCE_LIMIT }} }"
                    class="{{ $card }}">
                    @csrf

                    <h2 class="text-lg font-extrabold text-gray-900">Request a financial loan</h2>
                    <p class="mb-4 mt-1 text-sm text-gray-500">Your request is reviewed by HR first, then Accounting, with Admin providing final approval.</p>

                    <div class="grid gap-3.5 md:grid-cols-2">
                        <div class="flex flex-col gap-2 text-sm font-bold text-gray-600">
                            <span>Loan type <span class="text-[#b22222]">*</span></span>
                            <input type="hidden" name="loan_type" value="Cash Advance">
                            <div class="{{ $inputClass }} flex items-center text-left">Cash Advance</div>
                            @error('loan_type') <span class="text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <label for="loanAmount" class="flex flex-col gap-2 text-sm font-bold text-gray-600">
                            <span>Amount requested (PHP) <span class="text-[#b22222]">*</span></span>
                            <input type="number" id="loanAmount" name="amount" x-model="amount" min="0.01" step="0.01"
                                :max="type === 'Cash Advance' ? limit : null" placeholder="0.00" required class="{{ $inputClass }}">
                            <span x-show="type === 'Cash Advance'" x-cloak class="text-xs font-semibold text-gray-500">
                                Cash Advance is limited to ₱2,000.00.
                            </span>
                            @error('amount') <span class="text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label for="loanReason" class="mt-3.5 flex flex-col gap-2 text-sm font-bold text-gray-600">
                        <span>Purpose <span class="text-[#b22222]">*</span></span>
                        <textarea id="loanReason" name="reason" x-model="reason" rows="4" maxlength="1000" required
                            placeholder="Explain why you are requesting this loan." class="{{ $inputClass }}"></textarea>
                        <span class="flex justify-between text-xs font-semibold text-gray-400">
                            <span>Maximum 1,000 characters.</span>
                            <span x-text="reason.length + ' / 1000'"></span>
                        </span>
                        @error('reason') <span class="text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <div class="mt-3 flex justify-end">
                        <button type="submit"
                            class="rounded-lg bg-[#b22222] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#9a1d1d] focus:outline-none focus:ring-2 focus:ring-red-700/20">
                            Send for validation <i class="fa-solid fa-arrow-right ml-1.5"></i>
                        </button>
                    </div>
                </form>

                {{-- My loan requests --}}
                <div class="{{ $card }}" x-data="{ selecting: false, selected: [], ids: @js($loans->pluck('id')), reasonFor: null }">

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-gray-900">
                                {{ $archived ? 'Archived loan requests' : 'My loan requests' }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $archived ? 'Archived records stay available here and can be restored.' : 'Track the validation status of your submissions.' }}
                            </p>
                        </div>

                        @if (! $archived && $loans->isNotEmpty())
                            <button type="button" x-show="! selecting" @click="selecting = true; selected = []"
                                aria-label="Select loan requests to archive" title="Select loan requests to archive"
                                class="grid size-11 place-items-center rounded-xl border border-gray-200 text-gray-600 transition hover:bg-gray-50">
                                <i class="fa-solid fa-box-archive"></i>
                            </button>
                        @endif
                    </div>

                    {{-- Tabs --}}
                    <div class="hrms-tablist mt-4" role="tablist" aria-label="Loan request records">
                        @foreach ([false => 'Active requests', true => 'Archive'] as $isArchive => $label)
                            <a href="{{ route('loans.employee.index', $isArchive ? ['tab' => 'archived'] : []) }}"
                                role="tab" aria-selected="{{ $archived === $isArchive ? 'true' : 'false' }}"
                                class="hrms-tab text-sm font-bold">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    @if ($loans->isEmpty())
                        <div class="py-10 text-center text-gray-500">
                            <i class="fa-solid fa-inbox mb-3 text-4xl text-gray-300"></i>
                            <p class="font-semibold">
                                {{ $archived ? 'You have no archived loan requests.' : 'You have not submitted a loan request yet.' }}
                            </p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('loan.loan-employee.archive') }}"
                            :data-confirm="'Archive ' + selected.length + ' selected loan request(s)? You can restore them from the Archive tab.'"
                            data-confirm-button="Archive">
                            @csrf

                            {{-- Selection toolbar --}}
                            <div x-show="selecting" x-cloak class="mt-4 flex flex-wrap items-center gap-3 rounded-xl bg-gray-50 px-4 py-3 text-sm font-bold text-gray-600">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" class="size-4 accent-[#b22222]"
                                        :checked="selected.length === ids.length"
                                        @change="selected = $event.target.checked ? [...ids] : []">
                                    Select all
                                </label>
                                <span x-text="selected.length + ' selected'" class="text-gray-400"></span>
                                <span class="ml-auto flex gap-2">
                                    <button type="button" @click="selecting = false; selected = []"
                                        class="rounded-lg border border-gray-200 px-3 py-2 transition hover:bg-white">Cancel</button>
                                    <button type="submit" :disabled="selected.length === 0"
                                        class="rounded-lg bg-[#b22222] px-3 py-2 text-white transition hover:bg-[#9a1d1d] disabled:cursor-not-allowed disabled:opacity-40">
                                        Archive selected
                                    </button>
                                </span>
                            </div>

                            <div class="mt-4 flex flex-col gap-3.5">
                                @foreach ($loans as $loan)
                                    <article class="flex flex-col gap-3 rounded-lg border border-gray-200 p-3 transition lg:flex-row lg:items-center lg:justify-between">
                                        <div class="flex min-w-0 gap-3">
                                            @unless ($archived)
                                                <input type="checkbox" name="ids[]" value="{{ $loan->id }}" x-show="selecting" x-cloak
                                                    x-model.number="selected" aria-label="Select this request"
                                                    class="mt-1 size-4 shrink-0 accent-[#b22222]">
                                            @endunless
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-baseline gap-x-3">
                                                    <h3 class="font-extrabold text-gray-900">{{ $loan->loan_type }}</h3>
                                                    <strong class="text-[#b22222]">{{ $peso($loan->amount) }}</strong>
                                                </div>
                                                <p class="mt-1 break-words text-[15px] text-gray-600">{{ $loan->reason }}</p>
                                                <small class="text-gray-400">Sent {{ $loan->requested_at->format('M d, Y') }}</small>
                                            </div>
                                        </div>

                                        <div class="flex shrink-0 flex-wrap items-start gap-2" aria-label="Approval progress">
                                            @foreach (['hr' => 'HR', 'accounting' => 'Accounting', 'admin' => 'Admin'] as $key => $stage)
                                                @php
                                                    $s = $loan->effectiveStatus($key);
                                                    $rejectionReason = trim((string) ($loan->{$key . '_rejection_reason'} ?? ''));
                                                @endphp

                                                <button type="button"
                                                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1.5 text-xs font-bold transition {{ $stageBadge($s) }} {{ $s === 'rejected' && $rejectionReason !== '' ? 'cursor-pointer hover:opacity-90' : 'cursor-default' }}"
                                                        @if ($s === 'rejected' && $rejectionReason !== '')
                                                            @click="reasonFor = { title: @js($stage . ': ' . $stageLabel($stage, $s)), reason: @js($rejectionReason) }"
                                                        @endif
                                                        aria-label="{{ $s === 'rejected' && $rejectionReason !== '' ? 'View rejection reason for ' . $stage : null }}">
                                                    <span class="h-1.5 w-1.5 rounded-full {{ $s === 'approved' ? 'bg-green-600' : ($s === 'rejected' ? 'bg-red-600' : ($s === 'pending' ? 'bg-amber-500' : 'bg-gray-400')) }}"></span>
                                                    <span>{{ $stage }}: {{ $stageLabel($stage, $s) }}</span>
                                                </button>
                                            @endforeach

                                            @if ($archived)
                                                <button type="submit" form="restore-{{ $loan->id }}"
                                                    aria-label="Restore {{ $loan->loan_type }} loan request" title="Restore loan request"
                                                    class="grid size-11 place-items-center rounded-xl border border-gray-200 text-gray-600 transition hover:bg-gray-50">
                                                    <i class="fa-solid fa-box-open"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </form>

                        <div x-show="reasonFor" x-cloak x-transition.opacity @click.self="reasonFor = null"
                            class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-[rgba(17,24,39,0.55)] p-3 sm:p-5">
                            <section role="dialog" aria-modal="true"
                                class="relative max-h-[calc(100dvh-24px)] w-[min(420px,calc(100vw-24px))] overflow-y-auto rounded-[20px] bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] sm:rounded-[22px] sm:p-6">
                                <button type="button" @click="reasonFor = null" aria-label="Close rejection reason"
                                    class="absolute right-3 top-2.5 cursor-pointer text-2xl text-gray-500 hover:text-[#b22222]">&times;</button>
                                <h2 class="mb-2 mr-10 text-lg font-bold text-gray-900 sm:text-xl">Rejection reason</h2>
                                <p class="mb-3.5 text-sm font-semibold text-gray-500" x-text="reasonFor ? reasonFor.title : ''"></p>
                                <p class="whitespace-pre-wrap break-words rounded-xl border border-red-200 bg-red-50 p-3.5 text-sm leading-relaxed text-red-800"
                                    x-text="reasonFor ? reasonFor.reason : 'No rejection reason was provided.'"></p>
                            </section>
                        </div>

                        {{-- Restore forms live outside the archive form (nested forms aren't allowed) --}}
                        @if ($archived)
                            @foreach ($loans as $loan)
                                <form id="restore-{{ $loan->id }}" method="POST" action="{{ route('loan.loan-employee.restore', $loan) }}" class="hidden">
                                    @csrf
                                </form>
                            @endforeach
                        @endif

                        <div class="mt-4">{{ $loans->links() }}</div>
                    @endif
                </div>
            </section>
        @endif
    </div>
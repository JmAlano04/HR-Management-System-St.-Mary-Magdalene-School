{{-- resources/views/employee/loan-request.blade.php --}}

    {{-- Browser tab title + page heading --}}
    <x-slot name="title">
        Financial Loan Request
    </x-slot>

    @php
        $inputClass = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 text-[15px] text-gray-900 transition focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10';
        $card = 'rounded-[20px] bg-white p-[18px] sm:p-6 shadow-[0_10px_30px_rgba(15,23,42,0.05)]';

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
            <section aria-label="Financial Loan Request" class="flex flex-col gap-5">

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

                    <div class="grid grid-cols-2 gap-[14px] md:grid-cols-3 xl:grid-cols-6">
                        <div class="flex items-center gap-3.5 rounded-2xl bg-[#fff7f7] p-[18px] col-span-2 md:col-span-1">
                            <i class="fa-solid fa-wallet text-2xl text-[#b22222]"></i>
                            <div>
                                <p class="text-xl font-extrabold leading-none text-gray-900">{{ $peso($balances['total']) }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-500">Total outstanding</p>
                            </div>
                        </div>
                        @foreach ($balances['byType'] as $type => $amount)
                            <div class="rounded-2xl border border-gray-100 p-[18px]">
                                <p class="text-xl font-extrabold leading-none text-gray-900">{{ $peso($amount) }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-500">{{ $type }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Request form --}}
                <form method="POST" action="{{ route('loan.loan-employee.store') }}"
                    x-data="{ type: @js(old('loan_type', '')), amount: @js(old('amount', '')), reason: @js(old('reason', '')), limit: {{ \App\Models\LoanRequest::CASH_ADVANCE_LIMIT }} }"
                    class="{{ $card }}">
                    @csrf

                    <h2 class="text-lg font-extrabold text-gray-900">Request a financial loan</h2>
                    <p class="mb-4 mt-1 text-sm text-gray-500">Submit your request for review by HR, Accounting, and Admin.</p>

                    <div class="grid gap-3.5 md:grid-cols-2">
                        <label for="loanType" class="flex flex-col gap-2 text-sm font-bold text-gray-600">
                            <span>Loan type <span class="text-[#b22222]">*</span></span>
                            <select id="loanType" name="loan_type" x-model="type" required class="{{ $inputClass }}">
                                <option value="" disabled>Select a loan type</option>
                                @foreach (\App\Models\LoanRequest::TYPES as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                            @error('loan_type') <span class="text-xs font-semibold text-red-600">{{ $message }}</span> @enderror
                        </label>

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

                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                            class="rounded-xl bg-[#b22222] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#9a1d1d] focus:outline-none focus:ring-4 focus:ring-red-700/20">
                            Send for validation <i class="fa-solid fa-arrow-right ml-1.5"></i>
                        </button>
                    </div>
                </form>

                {{-- My loan requests --}}
                <div class="{{ $card }}" x-data="{ selecting: false, selected: [], ids: @js($loans->pluck('id')) }">

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
                    <div class="mt-4 flex gap-2 border-b border-gray-100" role="tablist" aria-label="Loan request records">
                        @foreach ([false => 'Active requests', true => 'Archive'] as $isArchive => $label)
                            <a href="{{ route('loan.loan-employee.index', $isArchive ? ['tab' => 'archived'] : []) }}"
                                role="tab" aria-selected="{{ $archived === $isArchive ? 'true' : 'false' }}"
                                class="-mb-px border-b-2 px-4 py-2.5 text-sm font-bold transition
                                    {{ $archived === $isArchive ? 'border-[#b22222] text-[#b22222]' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
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
                            @submit="if (! confirm('Archive ' + selected.length + ' selected loan request(s)? You can restore them from the Archive tab.')) $event.preventDefault()">
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
                                    <article class="flex flex-col gap-4 rounded-2xl border border-gray-100 p-4 transition hover:bg-[#fff7f7] lg:flex-row lg:items-center lg:justify-between">
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
                                                @php $s = $loan->effectiveStatus($key); @endphp
                                                <div class="max-w-[180px] rounded-xl px-3 py-2 text-xs {{ $stageBadge($s) }}">
                                                    <span class="block font-semibold opacity-70">{{ $stage }}</span>
                                                    <strong class="block font-bold">{{ $stageLabel($stage, $s) }}</strong>
                                                    @if ($s === 'rejected' && $loan->{$key . '_rejection_reason'})
                                                        <small class="mt-1 block break-words" title="{{ $loan->{$key . '_rejection_reason'} }}">
                                                            Reason: {{ $loan->{$key . '_rejection_reason'} }}
                                                        </small>
                                                    @endif
                                                </div>
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
{{-- resources/views/admin/leave-management/index.blade.php --}}

    {{-- Browser tab title --}}
    @auth
    <x-slot name="title">
        @if (auth()->user()->hasRole('Admin'))
            Admin Leave Management
        @elseif (auth()->user()->hasRole('HR'))
            HR Leave Management
        @elseif (auth()->user()->hasRole('Accounting'))
            Accounting Leave Management
        @elseif (auth()->user()->hasRole('Employee'))
            Employee Leave Management
        @elseif (auth()->user()->hasRole('Applicant'))
            Applicant Leave Management
        @else
            Dashboard
        @endif
    </x-slot>
@endauth

    {{-- Page heading (shown in the layout's top bar) --}}
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none  ml-20 tracking-tight text-[#b22222] sm:text-4xl">Leave Management</h1>
    </x-slot>

    @php
        // Look of the status badges
        $badge = fn (string $status) => match ($status) {
            'approved' => 'border-[#bbf7d0] bg-[#f0fdf4] text-[#166534]',
            'rejected' => 'border-[#fecaca] bg-[#fef2f2] text-[#b22222]',
            default    => 'border-[#fde68a] bg-[#fffbeb] text-[#92400e]',
        };

        $overview = [
            ['Pending Requests', $counts['pending']],
            ['Approved',         $counts['approved']],
            ['Rejected',         $counts['rejected']],
            ['Total Requests',   $counts['total']],
        ];
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="leaveRequests(@js([
            'items' => $cards,
            'balanceUrl' => route('leaves.balances'),
            'balanceResetUrl' => route('leaves.balances.reset'),
            'csrfToken' => csrf_token(),
        ]))"
        @keydown.escape.window="handleEscape()"
    >
        {{-- ============ Overview ============ --}}
        <section aria-label="Leave overview" class="mt-2 grid max-w-[1100px] gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($overview as [$label, $value])
                <div class="rounded-[18px] border border-[#f0dada] bg-white px-5 py-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <span class="block text-sm font-bold text-gray-900">{{ $label }}</span>
                    <strong class="mt-2 block text-3xl font-extrabold text-gray-900">{{ $value }}</strong>
                </div>
            @endforeach
        </section>

        <section aria-labelledby="leaveQueueTitle"
            class="mt-6 max-w-[1100px] rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">

            {{-- Heading --}}
            <div class="mb-6">
                <div>
                    <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Employee requests</p>
                    <h2 id="leaveQueueTitle" class="text-2xl font-bold text-gray-900">All leave requests</h2>
                    <p class="mt-2 text-gray-500">Review submitted leave requests, record your decision, and archive finished ones.</p>
                </div>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->has('decision') || $errors->has('rejection_reason'))
                <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    {{ $errors->first('decision') ?: $errors->first('rejection_reason') }}
                </div>
            @endif

            {{-- Tabs: Current / Archive --}}
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-[#f0dada]">
                <div class="hrms-tablist" role="tablist" aria-label="Leave request views">
                    @foreach (['current' => 'Current', 'archive' => 'Archive'] as $key => $label)
                        <a href="{{ route('leaves.index', ['view' => $key]) }}" role="tab" aria-selected="{{ $view === $key ? 'true' : 'false' }}"
                            class="hrms-tab font-extrabold">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                @if (auth()->user()->hasRole('HR'))
                    <button type="button" @click="openLeaveBalances()"
                        class="mb-1 inline-flex min-h-[44px] cursor-pointer items-center justify-center gap-2 rounded-[14px] bg-[#b22222] px-5 py-2.5 font-extrabold text-white transition hover:bg-[#951717] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#b22222] focus-visible:ring-offset-2">
                        <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                        Leave balances
                    </button>
                @endif
            </div>

            {{-- Request list --}}
            <div class="grid gap-3.5" aria-live="polite">
                @forelse ($requests as $req)
                    <div class="flex w-full items-center gap-3 rounded-[14px] border border-[#ecebea] bg-white pr-4 transition duration-200 hover:-translate-y-px hover:border-[#d9a5a5] hover:shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

                        {{-- Opens details --}}
                        <button type="button" @click="openDetail({{ $req->id }})"
                            class="flex min-w-0 flex-1 cursor-pointer flex-wrap items-center gap-x-4 gap-y-3 px-5 py-[18px] text-left">

                            <span class="min-w-0 flex-1">
                                <span class="block font-extrabold text-gray-900">{{ $req->employee_name }} - {{ $req->type }}</span>
                                <span class="mt-1.5 flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-600">
                                    <span><strong>Requested:</strong> {{ $req->requested_date->format('M d, Y') }}</span>
                                    <span><strong>Duration:</strong> {{ $req->date_range }}</span>
                                </span>
                            </span>

                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-[7px] text-[0.82rem] font-extrabold {{ $view === 'archive' ? 'border-[#ecebea] bg-white text-gray-500' : $badge($req->status) }}">
                                <i class="fa-solid fa-circle text-[0.58rem]" aria-hidden="true"></i>
                                {{ $view === 'archive' ? 'Archived' : $req->status_label }}
                            </span>
                        </button>

                        {{-- Archive (current view only) --}}
                        @if ($view === 'current' && in_array($req->status, ['approved', 'rejected'], true))
                            <form method="POST" action="{{ route('leave.archive', $req) }}" class="m-0">
                                @csrf
                                @method('PATCH')
                                <button type="submit" aria-label="Archive leave request" title="Archive"
                                    class="grid h-[38px] w-[38px] cursor-pointer place-items-center rounded-full border border-gray-300 bg-white text-gray-500 transition hover:border-[#b22222] hover:bg-[#fef2f2] hover:text-[#b22222]">
                                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                                </button>
                            </form>
                        @endif

                        <i class="fa-solid fa-chevron-right text-gray-400" aria-hidden="true"></i>
                    </div>
                @empty
                    <div class="rounded-[14px] border border-dashed border-[#e5caca] p-[30px] text-center text-gray-500">
                        {{ $view === 'archive' ? 'No archived leave requests.' : 'No leave requests found.' }}
                    </div>
                @endforelse
            </div>

            @if ($requests->hasPages())
                <div class="mt-5">{{ $requests->links() }}</div>
            @endif
        </section>

        {{-- ============ Details modal ============ --}}
        <div x-show="detail" x-cloak x-transition.opacity class="fixed inset-0 z-[60] grid place-items-center p-5">
            <div class="absolute inset-0 bg-[rgba(17,24,39,0.55)]" @click="detailId = null"></div>

            <section role="dialog" aria-modal="true" aria-labelledby="leaveModalTitle"
                class="relative max-h-[90vh] w-full max-w-[760px] overflow-auto rounded-[20px] bg-white p-[26px] shadow-[0_30px_80px_rgba(15,23,42,0.25)]">

                <div class="mb-[22px] flex items-start justify-between gap-5">
                    <div>
                        <h2 id="leaveModalTitle" class="text-[1.35rem] font-bold" x-text="detail ? detail.employee_name : ''"></h2>
                        <p class="mt-1 text-sm font-semibold text-gray-500" x-text="detail ? 'Employee ID: ' + detail.employee_code : ''"></p>
                        <p class="text-sm font-semibold text-gray-500" x-text="detail ? 'Department: ' + detail.department : ''"></p>
                    </div>
                    <button type="button" @click="detailId = null" aria-label="Close details"
                        class="h-9 w-9 shrink-0 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
                </div>

                <template x-if="detail">
                    <div>
                       <div class="grid gap-3">
                        @foreach ([
                            ['Leave type', 'type'],
                            ['Requested date', 'requested_date'],
                            ['Duration', 'duration'],
                            ['Total days', 'days'],
                            ['Reason', 'reason'],
                            ['Leave balance', 'leave_balance'],
                            ['Status', 'status'],
                            ['HR review', 'hr_status'],
                            ['Admin review', 'admin_status'],
                        ] as [$label, $key])
                            <div class="flex flex-col gap-1 border-b border-gray-100 pb-2.5 last:border-0 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                <span class="shrink-0 text-[0.78rem] font-bold uppercase tracking-[0.04em] text-gray-500">
                                    {{ $label }}
                                </span>
                                <span class="min-w-0 break-words text-gray-900 sm:text-right" x-text="detail?.{{ $key }}"></span>
                            </div>
                        @endforeach
                    </div>

                        {{-- Approve / Reject (only while the request is Pending) --}}
                        <template x-if="detail.can_decide">
                            <form method="POST" :action="detail.status_url" class="mt-6 flex justify-between gap-4">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="decision" :value="decision">
                                <input type="hidden" name="rejection_reason" :value="rejectionReason">

                                <button type="button" @click="submitDecision($event.currentTarget.closest('form'), 'approved')"
                                    class="cursor-pointer rounded-[10px] bg-[#15803d] px-[18px] py-[11px] font-extrabold text-white transition hover:-translate-y-px hover:brightness-95 hover:shadow-[0_8px_18px_rgba(15,23,42,0.12)]">
                                    Approve
                                </button>

                                <button type="button" @click="openRejectionModal()"
                                    class="cursor-pointer rounded-[10px] bg-[#b22222] px-[18px] py-[11px] font-extrabold text-white transition hover:-translate-y-px hover:brightness-95 hover:shadow-[0_8px_18px_rgba(15,23,42,0.12)]">
                                    Reject
                                </button>

                                <div x-show="rejectionModalOpen" x-cloak x-transition.opacity
                                    class="fixed inset-0 z-[80] grid place-items-center bg-black/50 p-4 sm:p-6"
                                    @click.self="rejectionModalOpen = false">
                                    <section role="dialog" aria-modal="true" aria-labelledby="leaveRejectionTitle"
                                        class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                                        <h2 id="leaveRejectionTitle" class="text-lg font-bold text-gray-900">Reject leave request</h2>
                                        <p class="mt-2 text-sm text-gray-600">Provide a reason for the employee.</p>
                                        <label for="leaveRejectionReason" class="mt-4 block text-sm font-semibold text-gray-700">
                                            Rejection reason <span class="text-red-600">*</span>
                                        </label>
                                        <textarea id="leaveRejectionReason" x-model="rejectionDraft" rows="4" maxlength="1000"
                                            @input="rejectionError = ''"
                                            class="mt-1 w-full rounded-lg border border-gray-300 p-3 text-sm text-gray-900 focus:border-[#b22222] focus:outline-none focus:ring-2 focus:ring-[#b22222]/10"
                                            placeholder="Explain why this request is rejected"></textarea>
                                        <p x-show="rejectionError" x-text="rejectionError" class="mt-1 text-sm font-semibold text-red-600" role="alert"></p>
                                        <div class="mt-5 flex justify-end gap-3">
                                            <button type="button" @click="rejectionModalOpen = false"
                                                class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                                                Cancel
                                            </button>
                                            <button type="button" @click="confirmRejection($event.currentTarget.closest('form'))"
                                                class="rounded-lg bg-[#b22222] px-4 py-2 text-sm font-semibold text-white hover:bg-[#951717]">
                                                Confirm rejection
                                            </button>
                                        </div>
                                    </section>
                                </div>
                            </form>
                        </template>
                        <p x-show="detail?.rejection_reason" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-800">
                            <strong>Rejection reason:</strong> <span x-text="detail?.rejection_reason"></span>
                        </p>
                    </div>
                </template>
            </section>
        </div>

        @if (auth()->user()->hasRole('HR'))
            <div x-show="leaveBalancesOpen" x-cloak x-transition.opacity
                @click.self="leaveBalancesOpen = false"
                class="fixed inset-0 z-[70] grid place-items-center bg-black/55 p-3 sm:p-5">
                <section role="dialog" aria-modal="true" aria-labelledby="leaveBalancesTitle"
                    class="flex max-h-[92vh] w-full max-w-[800px] flex-col overflow-hidden rounded-[24px] bg-white shadow-[0_28px_90px_rgba(0,0,0,0.3)]">
                    <header class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-5 sm:px-9 sm:py-7">
                        <div>
                            <h2 id="leaveBalancesTitle" class="text-xl font-extrabold tracking-tight text-gray-900 sm:text-2xl">
                                Employee Leave Balances
                            </h2>
                        </div>
                        <button type="button" @click="leaveBalancesOpen = false" aria-label="Close leave balances"
                            class="grid h-11 w-11 shrink-0 cursor-pointer place-items-center rounded-full bg-gray-100 text-[27px] leading-none text-gray-800 transition hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#b22222]">
                            &times;
                        </button>
                    </header>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-8 sm:py-7">
                        <div x-show="leaveBalancesLoading" role="status" class="py-12 text-center font-semibold text-gray-500">
                            Loading employee balances...
                        </div>

                        <div x-show="leaveBalancesError" x-cloak role="alert"
                            class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                            <p x-text="leaveBalancesError"></p>
                            <button type="button" @click="loadLeaveBalances()"
                                class="mt-3 rounded-lg bg-[#b22222] px-4 py-2 font-bold text-white hover:bg-[#951717]">
                                Try again
                            </button>
                        </div>

                        <div x-show="!leaveBalancesLoading && !leaveBalancesError && leaveBalances.length === 0"
                            class="py-12 text-center text-gray-500">
                            No linked employee records are available.
                        </div>

                        <div x-show="!leaveBalancesLoading && !leaveBalancesError && leaveBalances.length > 0"
                            class="overflow-hidden rounded-[16px] border border-gray-200">
                            <table class="w-full table-fixed text-left">
                                <thead class="bg-gray-50 text-xs font-extrabold uppercase tracking-[0.07em] text-gray-500">
                                    <tr>
                                        <th scope="col" class="w-[58%] px-4 py-4 sm:px-5">Employee</th>
                                        <th scope="col" class="px-4 py-4 sm:px-5">Total leave balance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="employee in leaveBalances" :key="employee.id">
                                        <tr class="bg-white transition-colors hover:bg-red-50/30">
                                            <td class="break-words px-4 py-4 sm:px-5">
                                                <span class="block font-extrabold text-[#9f2926]" x-text="employee.name"></span>
                                                <span class="mt-1 block text-sm font-semibold text-gray-500" x-text="employee.employeeId || 'No employee ID'"></span>
                                            </td>
                                            <td class="px-4 py-4 text-gray-600 sm:px-5">
                                                <span class="font-medium" x-text="employee.total.remaining"></span>
                                                <span x-text="employee.total.remaining === 1 ? ' day' : ' days'"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <footer class="flex flex-col-reverse gap-3 border-t border-gray-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-end sm:px-8">
                        <p x-show="leaveBalanceResetMessage" role="status"
                            class="text-sm font-semibold text-green-700" x-text="leaveBalanceResetMessage"></p>
                        <button type="button" @click="resetLeaveBalances()"
                            :disabled="leaveBalancesLoading || leaveBalancesResetting || leaveBalances.length === 0"
                            class="inline-flex min-h-[50px] cursor-pointer items-center justify-center gap-2 self-end rounded-[14px] bg-[#b22222] px-5 py-3 font-extrabold text-white transition hover:bg-[#951717] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#b22222] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:min-w-[250px]">
                            <i class="fa-solid fa-arrows-rotate" :class="{ 'animate-spin': leaveBalancesResetting }" aria-hidden="true"></i>
                            <span x-text="leaveBalancesResetting ? 'Resetting balances...' : 'Reset all balances'"></span>
                        </button>
                    </footer>
                </section>
            </div>
        @endif
    </div>

    <script>
        function leaveRequests(cfg) {
            return {
                items: cfg.items,
                detailId: null,
                rejectionModalOpen: false,
                rejectionDraft: '',
                rejectionReason: '',
                rejectionError: '',
                decision: '',
                balanceUrl: cfg.balanceUrl,
                balanceResetUrl: cfg.balanceResetUrl,
                csrfToken: cfg.csrfToken,
                leaveBalancesOpen: false,
                leaveBalancesLoading: false,
                leaveBalancesResetting: false,
                leaveBalancesError: '',
                leaveBalanceResetMessage: '',
                leaveBalances: [],
                leaveBalanceYear: null,

                get detail() {
                    return this.detailId ? this.items[this.detailId] : null;
                },

                openDetail(id) {
                    this.rejectionModalOpen = false;
                    this.detailId = id;
                },

                handleEscape() {
                    if (this.rejectionModalOpen) {
                        this.rejectionModalOpen = false;
                        return;
                    }

                    this.detailId = null;
                    this.leaveBalancesOpen = false;
                },

                openRejectionModal() {
                    this.rejectionDraft = '';
                    this.rejectionError = '';
                    this.rejectionModalOpen = true;
                },

                confirmRejection(form) {
                    const reason = this.rejectionDraft.trim();
                    if (!reason) {
                        this.rejectionError = 'Please enter a rejection reason.';
                        return;
                    }

                    this.rejectionReason = reason;
                    this.submitDecision(form, 'rejected');
                },

                submitDecision(form, decision) {
                    this.decision = decision;
                    this.$nextTick(() => form.requestSubmit());
                },

                openLeaveBalances() {
                    this.leaveBalancesOpen = true;
                    this.loadLeaveBalances();
                },

                async loadLeaveBalances() {
                    this.leaveBalancesLoading = true;
                    this.leaveBalancesError = '';

                    try {
                        const response = await fetch(this.balanceUrl, {
                            headers: { Accept: 'application/json' },
                            cache: 'no-store',
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            throw new Error(`Unable to load leave balances (HTTP ${response.status}).`);
                        }

                        const data = await response.json();
                        if (!Array.isArray(data.employees) || !Number.isInteger(data.year)) {
                            throw new Error('The leave balances response is invalid.');
                        }

                        this.leaveBalances = data.employees;
                        this.leaveBalanceYear = data.year;
                    } catch (error) {
                        console.error('Unable to load leave balances.', error);
                        this.leaveBalancesError = error.message || 'Unable to load leave balances. Please try again.';
                    } finally {
                        this.leaveBalancesLoading = false;
                    }
                },

                async resetLeaveBalances() {
                    if (this.leaveBalancesResetting || this.leaveBalances.length === 0) {
                        return;
                    }

                    const confirmed = window.confirm(
                        `Reset all ${this.leaveBalanceYear} leave balances? Previously approved leave will no longer count toward balances. Leave request records will be kept, and approvals after the reset will count again.`,
                    );
                    if (!confirmed) {
                        return;
                    }

                    this.leaveBalancesResetting = true;
                    this.leaveBalanceResetMessage = '';
                    this.leaveBalancesError = '';

                    try {
                        const response = await fetch(this.balanceResetUrl, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            cache: 'no-store',
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            throw new Error(`Unable to reset leave balances (HTTP ${response.status}).`);
                        }

                        const data = await response.json();
                        this.leaveBalanceResetMessage = data.message;
                        await this.loadLeaveBalances();
                    } catch (error) {
                        console.error('Unable to reset leave balances.', error);
                        this.leaveBalancesError = error.message || 'Unable to reset leave balances. Please try again.';
                    } finally {
                        this.leaveBalancesResetting = false;
                    }
                },
            };
        }
    </script>

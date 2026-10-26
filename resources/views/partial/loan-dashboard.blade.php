{{-- Needs: $loans, $awaitingAdmin, $tab, $month (from LoanController@index) --}}
@php
    $badge = [
        'locked'   => 'bg-gray-100 text-gray-600',
        'pending'  => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
    ];
    $stage = \App\Models\LoanRequest::stageForUser(auth()->user());
@endphp

<div class="space-y-5">
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

    {{-- Overview --}}
    <div class="flex items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
        <div>

           <h2 class="mt-4 mb-3 text-3xl font-semibold text-gray-800">Accounting-approved loan requests</h2>

            <p class="text-xs font-medium  text-gray-400">Admin review opens only after both HR and Accounting approve. </p>
            
        </div>
        <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
            <span class="h-2.5 w-2.5 rounded-full bg-red-600"></span>
            Awaiting Admin review: <span class="text-base font-bold">{{ $awaitingAdmin }}</span>
        </div>
    </div>

    {{-- Tabs + month filter (filter shows on Archive tab only) --}}
    <div class="rounded-2xl border border-gray-200 bg-white px-3 py-2 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 rounded-full bg-gray-100 p-1">
                <a href="{{ route('loans.index', ['tab' => 'active']) }}"
                   class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $tab === 'active' ? 'bg-red-700 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    Active
                </a>
                <a href="{{ route('loans.index', ['tab' => 'archive']) }}"
                   class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $tab === 'archive' ? 'bg-red-700 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
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

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Employee</th>
                        <th class="px-4 py-3 font-semibold">Type</th>
                        <th class="px-4 py-3 font-semibold">Amount</th>
                        <th class="px-4 py-3 font-semibold">Reason</th>
                        <th class="px-4 py-3 font-semibold">Requested</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach ($loans as $loan)
                        <tr class="align-top transition hover:bg-gray-50">
                            <td class="px-4 py-4 font-medium text-gray-800">
                                {{ $loan->employee?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-4">{{ $loan->loan_type }}</td>
                            <td class="px-4 py-4 font-semibold text-gray-900">{{ $loan->formatted_amount }}</td>
                            <td class="max-w-xs px-4 py-4 text-gray-600">
                                <div class="line-clamp-2">{{ $loan->reason }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-gray-600">{{ $loan->requested_at?->format('M d, Y') }}</td>
                            <td class="px-4 py-4">
                                <div class="space-y-2">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge[$loan->badge_key] ?? $badge['locked'] }}">
                                        {{ $loan->gate_message }}
                                    </span>
                                    @if ($loan->rejection_reason_text)
                                        <div class="text-xs text-red-700">Reason: {{ $loan->rejection_reason_text }}</div>
                                    @endif
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="min-w-[170px] px-4 py-4">
                                <div class="flex flex-col gap-2">

                                    {{-- Approve / Reject (Active tab, only when this user's stage is open) --}}
                                    @if ($tab === 'active' && $loan->canReviewAs($stage))
                                        <form method="POST" action="{{ route('loans.decide', $loan) }}"
                                              onsubmit="return confirm('Approve this loan request?')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="decision" value="approved">
                                            <button type="submit"
                                                    class="w-full rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-green-700">
                                                Approve
                                            </button>
                                        </form>

                                        <details class="group">
                                            <summary class="flex w-full cursor-pointer list-none items-center justify-center rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-red-700 [&::-webkit-details-marker]:hidden">
                                                Reject
                                            </summary>
                                            <form method="POST" action="{{ route('loans.decide', $loan) }}"
                                                  class="mt-2 space-y-2 rounded-xl border border-red-100 bg-red-50 p-2.5">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="decision" value="rejected">
                                                <textarea name="reason" rows="2" required maxlength="1000"
                                                          placeholder="Reason for rejection"
                                                          class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200"></textarea>
                                                <button type="submit"
                                                        class="w-full rounded-lg bg-red-700 px-3 py-2 text-sm font-medium text-white transition hover:bg-red-800">
                                                    Confirm reject
                                                </button>
                                            </form>
                                        </details>
                                    @endif

                                    {{-- Archive (Active tab, finished requests only) --}}
                                    @if ($tab === 'active' && $loan->can_archive)
                                        <form method="POST" action="{{ route('loans.archive', $loan) }}"
                                              onsubmit="return confirm('Archive this loan request?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="w-full rounded-lg bg-gray-700 px-3 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                                                Archive
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Restore (Archive tab) --}}
                                    @if ($tab === 'archive')
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500">
                                            Archived {{ $loan->staff_archived_at?->format('M d, Y') }}
                                        </div>
                                        <form method="POST" action="{{ route('loans.restore', $loan) }}"
                                              onsubmit="return confirm('Restore this loan request to Active?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                                                Restore
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Nothing to do --}}
                                    @if ($tab === 'active' && ! $loan->canReviewAs($stage) && ! $loan->can_archive)
                                        <span class="text-xs text-gray-400">No action available</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($loans->isEmpty())
            <div class="flex items-center justify-center py-12 text-center text-gray-500">
                @if ($tab === 'archive')
                    No archived loan requests{{ $month ? ' for ' . \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') : '' }}.
                @else
                    No loan requests.
                @endif
            </div>
        @endif
    </div>

    <div class="mt-4">
        {{ $loans->links() }}
    </div>
</div>
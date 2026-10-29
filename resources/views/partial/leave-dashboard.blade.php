{{-- resources/views/admin/leave-management/index.blade.php --}}

    {{-- Browser tab title --}}
    <x-slot name="title">
        Leave Management
    </x-slot>

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
        ]))"
        @keydown.escape.window="detailId = null"
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

            {{-- Heading + pending counter --}}
            <div class="mb-6 flex flex-col items-start justify-between gap-6 sm:flex-row">
                <div>
                    <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Employee requests</p>
                    <h2 id="leaveQueueTitle" class="text-2xl font-bold text-gray-900">All leave requests</h2>
                    <p class="mt-2 text-gray-500">Review submitted leave requests, record your decision, and archive finished ones.</p>
                </div>

                <div class="w-full min-w-[140px] rounded-[14px] bg-[#fef2f2] px-4 py-3 text-center text-[#b22222] sm:w-auto" aria-live="polite">
                    <strong class="block text-2xl">{{ $counts['pending'] }}</strong>
                    <span class="block text-xs font-bold">Pending review</span>
                </div>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->has('decision'))
                <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    {{ $errors->first('decision') }}
                </div>
            @endif

            {{-- Tabs: Current / Archive (+ Clear Archive) --}}
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-[#f0dada]">
                <div class="flex gap-2" role="tablist" aria-label="Leave request views">
                    @foreach (['current' => 'Current', 'archive' => 'Archive'] as $key => $label)
                        <a href="{{ route('leaves.index', ['view' => $key]) }}" role="tab" aria-selected="{{ $view === $key ? 'true' : 'false' }}"
                            class="-mb-px border-b-[3px] px-3.5 py-2.5 font-extrabold  transition {{ $view === $key ? 'text-[#b22222] bg-red-200 rounded-full' : 'border-transparent 0 text-gray-500 hover:text-gray-700' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                @if ($view === 'archive' && $requests->total() > 0)
                    <form method="POST" action="{{ route('leave.archive.clear') }}"
                        onsubmit="return confirm('Permanently delete all archived leave requests?');" class="mb-1.5">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[#dc2626] bg-white px-3.5 py-2 text-sm font-bold text-[#b91c1c] transition hover:bg-[#fef2f2]">
                        
                        </button>
                    </form>
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
                                {{ $view === 'archive' ? 'Archived' : ucfirst($req->status) }}
                            </span>
                        </button>

                        {{-- Archive (current view only) --}}
                        @if ($view === 'current')
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

                                <button type="submit" name="decision" value="approved"
                                    class="cursor-pointer rounded-[10px] bg-[#15803d] px-[18px] py-[11px] font-extrabold text-white transition hover:-translate-y-px hover:brightness-95 hover:shadow-[0_8px_18px_rgba(15,23,42,0.12)]">
                                    Approve
                                </button>

                                <button type="submit" name="decision" value="rejected"
                                    class="cursor-pointer rounded-[10px] bg-[#b22222] px-[18px] py-[11px] font-extrabold text-white transition hover:-translate-y-px hover:brightness-95 hover:shadow-[0_8px_18px_rgba(15,23,42,0.12)]">
                                    Reject
                                </button>
                            </form>
                        </template>
                    </div>
                </template>
            </section>
        </div>
    </div>

    <script>
        function leaveRequests(cfg) {
            return {
                items: cfg.items,
                detailId: null,

                get detail() {
                    return this.detailId ? this.items[this.detailId] : null;
                },

                openDetail(id) {
                    this.detailId = id;
                },
            };
        }
    </script>


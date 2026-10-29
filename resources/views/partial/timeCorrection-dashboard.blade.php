{{-- resources/views/time-corrections/index.blade.php --}}


    {{-- Browser tab title --}}
    <x-slot name="title">
        Time Correction Management
    </x-slot>

    {{-- Page heading (shown in the layout's top bar) --}}
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none  ml-20 tracking-tight text-[#b22222] sm:text-4xl">Time Correction Management</h1>
    </x-slot>

    @php
        // Look of the Admin / HR status badges
        $badge = fn (string $status) => match ($status) {
            'Approved' => 'border-[#bbf7d0] bg-[#f0fdf4] text-[#166534]',
            'Rejected' => 'border-[#fecaca] bg-[#fef2f2] text-[#b22222]',
            default    => 'border-[#ecebea] bg-white text-gray-500',
        };
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="timeCorrections(@js([
            'items' => $cards,
        ]))"
        @keydown.escape.window="viewer ? viewer = false : detailId = null"
    >
        <section aria-labelledby="correctionQueueTitle"
            class="mt-2 max-w-[1100px] rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">

            {{-- Heading + pending counter --}}
            <div class="mb-6 flex flex-col items-start justify-between gap-6 sm:flex-row">
                <div>
                    <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Employee requests</p>
                    <h2 id="correctionQueueTitle" class="text-2xl font-bold text-gray-900">Time correction requests</h2>
                    <p class="mt-2 text-gray-500">Review submitted logbook corrections and record your decision.</p>
                </div>

                <div class="w-full min-w-[140px] rounded-[14px] bg-[#fef2f2] px-4 py-3 text-center text-[#b22222] sm:w-auto" aria-live="polite">
                    <strong class="block text-2xl">{{ $pendingCount }}</strong>
                    <span class="block text-xs font-bold">Pending {{ $reviewer }} review</span>
                </div>
            </div>

            {{-- Flash message --}}
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

            {{-- Tabs: Current / Archive --}}
            <div class="mb-4 flex gap-2 border-b border-[#f0dada]" role="tablist" aria-label="Time correction request views">
                @foreach (['current' => 'Current', 'archive' => 'Archive'] as $key => $label)
                    <a href="{{ route('time-corrections.index', ['view' => $key]) }}" role="tab" aria-selected="{{ $view === $key ? 'true' : 'false' }}"
                        class="-mb-px border-b-[3px] px-3.5 py-2.5 font-extrabold transition {{ $view === $key ? 'bg-red-200 rounded-full text-[#b22222]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Request list --}}
            <div class="grid gap-3.5" aria-live="polite">
                @forelse ($requests as $req)
                    <button type="button" @click="openDetail({{ $req->id }})"
                        class="flex w-full cursor-pointer flex-wrap items-center gap-x-4 gap-y-3 rounded-[14px] border border-[#ecebea] bg-white px-5 py-[18px] text-left transition duration-200 hover:-translate-y-px hover:border-[#d9a5a5] hover:shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

                        <span class="min-w-0 flex-1 font-extrabold text-gray-900">{{ $req->employee?->name ?? 'Unknown employee' }}</span>

                        <span class="flex flex-wrap justify-end gap-2" aria-label="Review statuses">
                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-[7px] text-[0.82rem] font-extrabold {{ $badge($req->admin_status) }}">
                                <i class="fa-solid fa-circle text-[0.58rem]" aria-hidden="true"></i> Admin: {{ $req->admin_status }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-[7px] text-[0.82rem] font-extrabold {{ $badge($req->hr_status) }}">
                                <i class="fa-solid fa-circle text-[0.58rem]" aria-hidden="true"></i> HR: {{ $req->hr_status }}
                            </span>
                        </span>

                        <i class="fa-solid fa-chevron-right text-gray-400" aria-hidden="true"></i>
                    </button>
                @empty
                    <div class="rounded-[14px] border border-dashed border-[#e5caca] p-[30px] text-center text-gray-500">
                        {{ $view === 'archive' ? 'No completed requests in the archive.' : 'No pending employee time-correction requests.' }}
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

            <section role="dialog" aria-modal="true" aria-labelledby="correctionModalTitle"
                class="relative max-h-[90vh] w-full max-w-[760px] overflow-auto rounded-[20px] bg-white p-[26px] shadow-[0_30px_80px_rgba(15,23,42,0.25)]">

                <div class="mb-[22px] flex items-center justify-between gap-5">
                    <h2 id="correctionModalTitle" class="text-[1.35rem] font-bold">Time correction details</h2>
                    <button type="button" @click="detailId = null" aria-label="Close details"
                        class="h-9 w-9 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
                </div>

                <template x-if="detail">
                    <div>
                        <div class="grid gap-6 sm:grid-cols-[minmax(0,1fr)_minmax(220px,0.8fr)]">
                            {{-- Details --}}
                            <div class="grid gap-3">
                                @foreach ([
                                    ['Employee', 'employee_name'],
                                    ['Date', 'date_label'],
                                    ['Time entry type', 'type'],
                                    ['Incorrect time', 'incorrect_time'],
                                    ['Correct time', 'correct_time'],
                                    ['Note', 'note'],
                                ] as [$label, $key])
                                    <p class="grid gap-1 border-b border-gray-100 pb-2.5">
                                        <strong class="text-[0.78rem] uppercase tracking-[0.04em] text-gray-500">{{ $label }}</strong>
                                        <span x-text="detail.{{ $key }}"></span>
                                    </p>
                                @endforeach
                            </div>

                            {{-- Image proof --}}
                            <div class="grid content-start gap-2.5 text-gray-500">
                                <strong class="text-[0.78rem] uppercase tracking-[0.04em]">Image proof</strong>

                                <template x-if="detail.proof_url && !proofError">
                                    <img :src="detail.proof_url" alt="Submitted logbook proof" @click="viewer = true" x-on:error="proofError = true"
                                        class="max-h-80 w-full cursor-zoom-in rounded-xl bg-gray-50 object-contain">
                                </template>
                                <span x-show="!detail.proof_url" x-text="'No image proof attached.'"></span>
                                <span x-show="detail.proof_url && proofError">Image proof unavailable for this request.</span>
                            </div>
                        </div>

                        {{-- Approve / Reject (only while this reviewer's status is Pending) --}}
                      <template x-if="detail.can_decide">
    <form
        method="POST"
        action="{{ route('decide') }}"
        class="mt-6 flex justify-between gap-4"
    >
        @csrf
        @method('PATCH')

        <input
            type="hidden"
            name="time_correction_id"
            :value="detailId"
        >

        <button
            type="submit"
            name="decision"
            value="Approved"
            class="cursor-pointer rounded-[10px] bg-[#15803d] px-[18px] py-[11px] font-extrabold text-white transition hover:-translate-y-px hover:brightness-95 hover:shadow-[0_8px_18px_rgba(15,23,42,0.12)]"
        >
            Approve
        </button>

        <button
            type="submit"
            name="decision"
            value="Rejected"
            class="cursor-pointer rounded-[10px] bg-[#b22222] px-[18px] py-[11px] font-extrabold text-white transition hover:-translate-y-px hover:brightness-95 hover:shadow-[0_8px_18px_rgba(15,23,42,0.12)]"
        >
            Reject
        </button>
    </form>
</template>
                    </div>
                </template>
            </section>
        </div>

        {{-- ============ Image proof viewer ============ --}}
        <div x-show="viewer" x-cloak x-transition.opacity class="fixed inset-0 z-[70] grid place-items-center p-6">
            <div class="absolute inset-0 bg-[rgba(17,24,39,0.78)]" @click="viewer = false"></div>

            <section role="dialog" aria-modal="true" aria-labelledby="proofViewerTitle"
                class="relative max-h-[94vh] w-full max-w-[1000px] rounded-[18px] bg-white p-[22px] shadow-[0_30px_90px_rgba(0,0,0,0.3)]">
                <div class="mb-4 flex items-center justify-between gap-5">
                    <h2 id="proofViewerTitle" class="text-xl font-bold">Image proof</h2>
                    <button type="button" @click="viewer = false" aria-label="Close image viewer"
                        class="h-9 w-9 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
                </div>
                <img :src="detail ? detail.proof_url : ''" alt="Enlarged submitted logbook proof"
                    class="max-h-[78vh] w-full rounded-[10px] bg-gray-100 object-contain">
            </section>
        </div>
    </div>

    <script>
        function timeCorrections(cfg) {
            return {
                items: cfg.items,
                detailId: null,
                viewer: false,
                proofError: false,

                get detail() {
                    return this.detailId ? this.items[this.detailId] : null;
                },

                openDetail(id) {
                    this.proofError = false;
                    this.viewer = false;
                    this.detailId = id;
                },
            };
        }
    </script>

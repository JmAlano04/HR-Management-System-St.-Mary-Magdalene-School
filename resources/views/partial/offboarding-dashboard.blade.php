{{-- resources/views/admin/offboarding-management/index.blade.php --}}

    {{-- Browser tab title --}}
    <x-slot name="title">
        Offboarding Dashboard
    </x-slot>

    {{-- Page heading (shown in the layout's top bar) --}}
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none  ml-20 tracking-tight text-[#b22222] sm:text-4xl">Offboarding Management</h1>
    </x-slot>

    @php
        $statCards = [
            ['fa-clipboard-check', $stats['pending'],   'Awaiting final approval'],
            ['fa-circle-check',    $stats['completed'], 'Final approvals'],
        ];
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="offboardingReview(@js([
            'items' => $cards,
        ]))"
        @keydown.escape.window="detailId = null"
    >
        {{-- ============ Stat cards ============ --}}
        <section aria-label="Offboarding approvals" class="mt-2 grid max-w-[1100px] gap-4 sm:grid-cols-2">
            @foreach ($statCards as [$icon, $value, $label])
                <article class="grid grid-cols-[auto_1fr] items-center gap-x-3.5 gap-y-1 rounded-[18px] border border-[#f0dada] bg-white px-5 py-[18px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <div class="row-span-2 grid h-[42px] w-[42px] place-items-center rounded-lg bg-[#fef2f2] text-[#b22222]">
                        <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
                    </div>
                    <div class="text-2xl font-extrabold text-gray-900">{{ $value }}</div>
                    <div class="text-sm text-gray-500">{{ $label }}</div>
                </article>
            @endforeach
        </section>

        <section aria-labelledby="offboardingCasesTitle"
            class="mt-6 max-w-[1100px] rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">

            {{-- Heading --}}
            <div class="mb-6">
                <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Offboarding</p>
                <h2 id="offboardingCasesTitle" class="text-2xl font-bold text-gray-900">HR-approved offboarding cases</h2>
                <p class="mt-2 text-gray-500">Review the case and submitted documents before final approval.</p>
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

            {{-- Cases table --}}
            <div class="overflow-x-auto rounded-[14px] border border-[#ecebea]">
                <table class="w-full min-w-[720px] border-collapse bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach (['Employee', 'Reason', 'Last Working Day', 'Documents', 'Status', 'Review'] as $heading)
                                <th class="whitespace-nowrap border-b border-gray-200 px-3.5 py-3 text-left text-xs font-extrabold uppercase text-gray-500">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cases as $case)
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="border-b border-gray-100 px-3.5 py-3">
                                    <div class="grid gap-0.5">
                                        <span class="font-bold text-gray-900">{{ $case->employee?->name ?? 'Unknown employee' }}</span>
                                        <span class="text-[0.85rem] text-gray-500">{{ $case->employee?->position ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="border-b border-gray-100 px-3.5 py-3 text-sm text-gray-800">{{ $case->reason }}</td>
                                <td class="whitespace-nowrap border-b border-gray-100 px-3.5 py-3 text-sm text-gray-800">{{ $case->last_working_day->format('M d, Y') }}</td>
                                <td class="border-b border-gray-100 px-3.5 py-3 text-sm text-gray-800">{{ $case->documents->count() }}</td>
                                <td class="border-b border-gray-100 px-3.5 py-3">
                                    <span class="inline-flex items-center whitespace-nowrap rounded-md px-2.5 py-1.5 text-[0.82rem] font-bold {{ $case->admin_final_approved ? 'bg-[#e8f5ec] text-[#21683a]' : 'bg-[#fff5d6] text-[#8a5a00]' }}">
                                        {{ $case->status_label }}
                                    </span>
                                </td>
                                <td class="border-b border-gray-100 px-3.5 py-3">
                                    <button type="button" @click="openDetail({{ $case->id }})"
                                        class="min-h-[38px] cursor-pointer rounded-md border border-[#b22222] bg-[#b22222] px-3 py-2 font-bold text-white transition hover:bg-white hover:text-[#b22222]">
                                        Review
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3.5 py-[22px] text-center text-gray-500">
                                    No cases have been sent by HR for final approval.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($cases->hasPages())
                <div class="mt-5">{{ $cases->links() }}</div>
            @endif
        </section>

        {{-- ============ Final review modal ============ --}}
        <div x-show="detail" x-cloak x-transition.opacity class="fixed inset-0 z-[60] grid place-items-center p-5">
            <div class="absolute inset-0 bg-[rgba(17,24,39,0.55)]" @click="detailId = null"></div>

            <section role="dialog" aria-modal="true" aria-labelledby="adminReviewTitle"
                class="relative max-h-[90vh] w-full max-w-[760px] overflow-auto rounded-[20px] bg-white p-[26px] shadow-[0_30px_80px_rgba(15,23,42,0.25)]">

                <div class="mb-[18px] flex items-center justify-between gap-3">
                    <h2 id="adminReviewTitle" class="text-[1.2rem] font-bold">Final offboarding review</h2>
                    <button type="button" @click="detailId = null" aria-label="Close final review"
                        class="h-9 w-9 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
                </div>

                <template x-if="detail">
                    <div class="grid gap-5">
                        {{-- Case summary --}}
                        <section class="rounded-lg border border-[#fef2f2] bg-gray-50 p-4">
                            <h3 class="mb-3 text-[1.05rem] font-bold" x-text="detail.employee_name"></h3>
                            <dl class="m-0 grid gap-3 sm:grid-cols-3">
                                <div class="grid gap-1">
                                    <dt class="text-[0.8rem] text-gray-500">Position</dt>
                                    <dd class="m-0 font-bold" x-text="detail.position"></dd>
                                </div>
                                <div class="grid gap-1">
                                    <dt class="text-[0.8rem] text-gray-500">Reason</dt>
                                    <dd class="m-0 font-bold" x-text="detail.reason"></dd>
                                </div>
                                <div class="grid gap-1">
                                    <dt class="text-[0.8rem] text-gray-500">Last working day</dt>
                                    <dd class="m-0 font-bold" x-text="detail.last_working_day"></dd>
                                </div>
                            </dl>
                            <p class="mt-3.5 leading-relaxed text-gray-500">
                                <strong class="text-gray-700">Message</strong><br>
                                <span x-text="detail.message"></span>
                            </p>
                        </section>

                        {{-- HR-reviewed documents --}}
                        <section class="rounded-lg border border-[#fef2f2] bg-gray-50 p-4">
                            <h3 class="mb-3 text-[1.05rem] font-bold">HR-reviewed documents</h3>

                            <ul class="m-0 grid list-none gap-2 p-0">
                                <template x-for="doc in detail.documents" :key="doc.id">
                                    <li class="flex items-center justify-between gap-3.5 border-b border-[#fef2f2] py-2.5 last:border-b-0">
                                        <div class="grid min-w-0 gap-1">
                                            <strong x-text="doc.label"></strong>
                                            <a x-show="doc.url" :href="doc.url" target="_blank" rel="noopener"
                                                class="break-all font-semibold text-[#b22222] underline" x-text="doc.name"></a>
                                            <span x-show="!doc.url" class="break-all text-sm text-gray-400" x-text="doc.name + ' (file unavailable)'"></span>
                                        </div>
                                        <span class="inline-flex items-center whitespace-nowrap rounded-md px-2.5 py-1.5 text-[0.82rem] font-bold"
                                            :class="doc.hr_ok ? 'bg-[#e8f5ec] text-[#21683a]' : 'bg-[#fff5d6] text-[#8a5a00]'"
                                            x-text="doc.hr_ok ? 'HR approved' : doc.status"></span>
                                    </li>
                                </template>

                                <li x-show="!detail.documents.length" class="text-gray-500">No submitted documents are available.</li>
                            </ul>
                        </section>

                        {{-- Final approve --}}
                        <div class="flex flex-col items-stretch justify-between gap-3.5 sm:flex-row sm:items-center">
                            <p role="status" class="m-0 text-gray-500" x-text="detail.status_message"></p>

                            <form method="POST" :action="detail.approve_url" class="m-0"
                                onsubmit="return confirm('Give final approval to this offboarding case?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" :disabled="!detail.can_approve"
                                    class="min-h-[38px] w-full cursor-pointer rounded-md border border-[#b22222] bg-[#b22222] px-3 py-2 font-bold text-white transition hover:bg-white hover:text-[#b22222] disabled:cursor-not-allowed disabled:border-gray-300 disabled:bg-gray-100 disabled:text-gray-500 disabled:hover:bg-gray-100 disabled:hover:text-gray-500 sm:w-auto"
                                    x-text="detail.approved ? 'Final approved' : 'Final approve'"></button>
                            </form>
                        </div>
                    </div>
                </template>
            </section>
        </div>
    </div>

    <script>
        function offboardingReview(cfg) {
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


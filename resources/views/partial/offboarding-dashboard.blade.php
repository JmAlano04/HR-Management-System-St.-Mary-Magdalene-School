{{-- resources/views/admin/offboarding-management/index.blade.php --}}

    {{-- Browser tab title --}}
     @auth
    <x-slot name="title">
        @if (auth()->user()->hasRole('Admin'))
            Admin Offboarding Management
        @elseif (auth()->user()->hasRole('HR'))
            HR Offboarding Management
        @elseif (auth()->user()->hasRole('Accounting'))
            Accounting Offboarding Management
        @elseif (auth()->user()->hasRole('Employee'))
            Employee Offboarding Management
        @elseif (auth()->user()->hasRole('Applicant'))
            Applicant Dashboard
        @else
            Dashboard
        @endif
    </x-slot>
@endauth
    {{-- Page heading (shown in the layout's top bar) --}}
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none  ml-20 tracking-tight text-[#b22222] sm:text-4xl">Offboarding Management</h1>
    </x-slot>

    @php
<<<<<<< HEAD
        $isHr = auth()->user()->hasRole('HR');
        $statCards = [
            [$stats['pending'], $isHr ? 'In progress' : 'Current'],
            [$stats['completed'], $isHr ? 'Archived' : 'Archive'],
=======
        $statCards = [
            ['fa-clipboard-check', $stats['pending'],   'Awaiting final approval'],
            ['fa-circle-check',    $stats['completed'], 'Final approvals'],
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        ];
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="offboardingReview(@js([
            'items' => $cards,
<<<<<<< HEAD
            'openId' => session('offboarding_detail_id'),
        ]))"
        @keydown.escape.window="rejection ? closeRejection() : detailId = null"
    >
        {{-- ============ Stat cards ============ --}}
        <section aria-label="Offboarding approvals" class="mt-2 grid max-w-[1100px] gap-4 sm:grid-cols-2">
            @foreach ($statCards as [$value, $label])
                <article class="grid grid-cols-1 gap-y-1 rounded-[18px] border border-[#f0dada] bg-white px-5 py-[18px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <div class="text-2xl font-extrabold text-gray-900">{{ $value }}</div>
                    <div class="text-sm text-gray-500">{{ $label }}</div>
=======
        ]))"
        @keydown.escape.window="detailId = null"
    >
        {{-- ============ Stat cards ============ --}}
        <section aria-label="Offboarding approvals" class="mt-2 grid max-w-[1100px] gap-4 sm:grid-cols-2">
            @foreach ($statCards as [$icon, $value, $label])
                <article class="grid grid-cols-[auto_1fr] items-center gap-x-3.5 gap-y-1 rounded-[18px] border border-[#f0dada] bg-white px-5 py-[18px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                   <div class="text-sm text-gray-500">{{ $label }}</div>
                   <br>
                    <div class="text-2xl font-extrabold text-gray-900">{{ $value }}</div>
                    
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                </article>
            @endforeach
        </section>

        <section aria-labelledby="offboardingCasesTitle"
            class="mt-6 max-w-[1100px] rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">

            {{-- Heading --}}
            <div class="mb-6">
<<<<<<< HEAD
                <h2 id="offboardingCasesTitle" class="text-3xl font-extrabold text-gray-900">
                    {{ $isHr
                        ? ($tab === 'current' ? 'Current offboarding list' : 'Archived offboarding list')
                        : ($tab === 'current' ? 'Current offboarding requests from HR' : 'Archived offboarding requests') }}
                </h2>
                <p class="mt-2 text-gray-500">
                    {{ $isHr ? 'Select a request to open the employee tracking view.' : 'Select a request to review the case and submitted documents.' }}
                </p>
            </div>

            <nav aria-label="Offboarding request list" class="mb-6 flex items-center gap-3">
                    <a href="{{ route('offboarding.index', ['tab' => 'current']) }}"
                        @class([
                            'rounded-full border px-6 py-3 text-lg font-extrabold transition',
                            'border-red-200 bg-red-100 text-red-800' => $tab === 'current',
                            'border-transparent bg-transparent text-gray-500 hover:bg-gray-50' => $tab !== 'current',
                        ])
                        @if ($tab === 'current') aria-current="page" @endif>
                        Current
                    </a>
                    <a href="{{ route('offboarding.index', ['tab' => 'archive']) }}"
                        @class([
                            'rounded-full border px-6 py-3 text-lg font-extrabold transition',
                            'border-red-200 bg-red-100 text-red-800' => $tab === 'archive',
                            'border-transparent bg-transparent text-gray-500 hover:bg-gray-50' => $tab !== 'archive',
                        ])
                        @if ($tab === 'archive') aria-current="page" @endif>
                        Archive
                    </a>
            </nav>

            @if ($tab === 'archive')
                <form method="GET" action="{{ route('offboarding.index') }}"
                    class="mb-6 grid gap-3 rounded-xl border border-[#f0dada] bg-gray-50 p-4 sm:grid-cols-[minmax(220px,1fr)_minmax(150px,0.6fr)_minmax(120px,0.5fr)_auto_auto] sm:items-end">
                    <input type="hidden" name="tab" value="archive">
                    <div class="grid gap-1.5">
                        <label for="archiveEmployeeFilter" class="text-sm font-bold text-gray-700">Employee</label>
                        <input id="archiveEmployeeFilter" type="search" name="employee" value="{{ $employeeFilter }}"
                            placeholder="Search employee name"
                            class="min-h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#b22222] focus:outline-none focus:ring-1 focus:ring-[#b22222]">
                    </div>
                    <div class="grid gap-1.5">
                        <label for="archiveMonthFilter" class="text-sm font-bold text-gray-700">Month</label>
                        <select id="archiveMonthFilter" name="month"
                            class="min-h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#b22222] focus:outline-none focus:ring-1 focus:ring-[#b22222]">
                            <option value="">All months</option>
                            @foreach (range(1, 12) as $month)
                                <option value="{{ $month }}" @selected((string) $monthFilter === (string) $month)>
                                    {{ \Carbon\Carbon::create()->month($month)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-1.5">
                        <label for="archiveYearFilter" class="text-sm font-bold text-gray-700">Year</label>
                        <input id="archiveYearFilter" type="number" name="year" value="{{ $yearFilter }}"
                            min="2000" max="{{ now()->year }}" placeholder="Any year"
                            class="min-h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-[#b22222] focus:outline-none focus:ring-1 focus:ring-[#b22222]">
                    </div>
                    <button type="submit"
                        class="min-h-10 cursor-pointer rounded-md bg-[#b22222] px-5 py-2 text-sm font-bold text-white transition hover:bg-[#921c1c]">
                        Filter
                    </button>
                    <a href="{{ route('offboarding.index', ['tab' => 'archive']) }}"
                        class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-100">
                        Clear
                    </a>
                </form>
            @endif

            {{-- Flash messages --}}
            @if (session('success') && ! session('offboarding_detail_id'))
=======
                <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Offboarding</p>
                <h2 id="offboardingCasesTitle" class="text-2xl font-bold text-gray-900">HR-approved offboarding cases</h2>
                <p class="mt-2 text-gray-500">Review the case and submitted documents before final approval.</p>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] border-collapse bg-white">
                    <thead>
                        <tr>
                            @foreach ($isHr ? ['Employee', 'Reason', 'Last working day', 'Progress', 'Status'] : ['Employee', 'Reason', 'Last working day', 'Documents', 'Status'] as $heading)
                                <th class="whitespace-nowrap px-4 py-4 text-center text-sm font-extrabold uppercase tracking-wide text-gray-900">{{ $heading }}</th>
=======
            <div class="overflow-x-auto rounded-[14px] border border-[#ecebea]">
                <table class="w-full min-w-[720px] border-collapse bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach (['Employee', 'Reason', 'Last Working Day', 'Documents', 'Status', 'Review'] as $heading)
                                <th class="whitespace-nowrap border-b border-gray-200 px-3.5 py-3 text-left text-xs font-extrabold uppercase text-gray-500">{{ $heading }}</th>
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cases as $case)
<<<<<<< HEAD
                            @php
                                $statusLabel = $case->admin_final_approved
                                    ? $case->status_label
                                    : ($isHr
                                        ? ($case->status === 'Rejected' ? 'Rejected' : ($case->sent_to_admin ? 'Forwarded to Admin' : ($case->submitted_required_document_count > 0 ? 'Documents submitted' : ($case->requirementsSent() ? 'Awaiting employee documents' : 'Awaiting HR review'))))
                                        : $case->status_label);
                                $statusStyle = $case->admin_final_approved
                                    ? 'bg-[#e8f5ec] text-[#21683a]'
                                    : ($case->status === 'Rejected' ? 'bg-[#fdeaea] text-[#b22222]' : 'bg-[#fff5d6] text-[#8a5a00]');
                            @endphp
                            <tr tabindex="0" role="button"
                                aria-label="Review offboarding request for {{ $case->employee?->name ?? 'Unknown employee' }}"
                                @click="openDetail({{ $case->id }})"
                                @keydown.enter.prevent="openDetail({{ $case->id }})"
                                @keydown.space.prevent="openDetail({{ $case->id }})"
                                class="cursor-pointer border-b border-[#f0dada] transition-colors hover:bg-gray-50/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#b22222]">
                                <td class="px-4 py-6">
                                    <div class="grid gap-0.5">
                                        <span class="font-extrabold text-gray-900">{{ $case->employee?->name ?? 'Unknown employee' }}</span>
                                        <span class="text-gray-500">{{ $case->employee?->position ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-6 text-gray-800">{{ $case->reason }}</td>
                                <td class="whitespace-nowrap px-4 py-6 text-center text-gray-800">{{ $case->last_working_day->format('m/d/Y') }}</td>
                                @if ($isHr)
                                    <td class="px-4 py-6">
                                        <div class="relative mx-auto flex h-9 w-36 items-center rounded-full bg-gray-200 p-0.5">
                                            <div class="h-8 rounded-full bg-[#c9232c] transition-[width]"
                                                style="width: {{ min(100, max(0, (int) $case->progress)) }}%"></div>
                                            <span class="absolute inset-0 grid place-items-center text-sm font-extrabold text-gray-900">{{ min(100, max(0, (int) $case->progress)) }}%</span>
                                        </div>
                                        <span class="mt-1 block text-center text-xs text-gray-500">
                                            {{ $case->submitted_required_document_count }}/{{ count($case->uploadable_document_labels) }} documents submitted
                                        </span>
                                    </td>
                                @else
                                    <td class="px-4 py-6 text-center text-gray-800">{{ $case->documents->count() }}</td>
                                @endif
                                <td class="px-4 py-6 text-center">
                                    <span class="inline-flex items-center whitespace-nowrap rounded-full px-4 py-2 text-sm font-extrabold {{ $statusStyle }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-gray-500">
                                    @if ($isHr)
                                        {{ $tab === 'current' ? 'No offboarding requests are in progress.' : 'No offboarding decisions have been completed.' }}
                                    @else
                                        {{ $tab === 'current' ? 'No offboarding requests are awaiting your decision.' : 'No offboarding decisions have been completed.' }}
                                    @endif
=======
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
                class="relative max-h-[94vh] w-full max-w-[1040px] overflow-auto rounded-[20px] bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.25)] sm:p-7 lg:p-8">

                <div class="mb-5 flex items-start justify-between gap-3">
                    <div>
                        @if ($isHr)
                            <h2 id="adminReviewTitle" class="text-xl font-extrabold sm:text-2xl" x-text="detail ? detail.employee_name : 'Employee offboarding request'"></h2>
                            <p class="text-sm text-gray-500">
                                <span x-text="detail ? detail.position : ''"></span> · <span x-text="detail ? detail.reason : ''"></span>
                            </p>
                        @else
                            <h2 id="adminReviewTitle" class="text-xl font-extrabold sm:text-2xl" x-text="detail ? detail.employee_name : 'Employee offboarding request'"></h2>
                            <p class="text-sm text-gray-500">
                                <span x-text="detail ? detail.position : ''"></span> · <span x-text="detail ? detail.reason : ''"></span>
                            </p>
                        @endif
                    </div>
                    <button type="button" @click="detailId = null" aria-label="Close final review"
                        class="h-8 w-8 shrink-0 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
                </div>

                <template x-if="detail">
                    @if ($isHr)
                        <div class="grid gap-4 text-sm">
                            @if (session('success'))
                                <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                                    {{ session('success') }}
                                </div>
                            @endif
                            <section class="rounded-xl border border-[#f0dada] bg-gray-50 p-4">
                                <h4 class="mb-2 text-base font-extrabold text-gray-900">Employee request</h4>
                                <div class="grid grid-cols-1 items-start gap-3 rounded-lg border border-[#f0dada] bg-white p-3 sm:grid-cols-[1fr_auto]">
                                    <div class="grid gap-1">
                                        <span class="text-xs font-bold text-gray-500">Last working day</span>
                                        <strong class="text-sm text-gray-800" x-text="detail.last_working_day"></strong>
                                    </div>
                                    <div class="max-w-[420px] text-left sm:text-right">
                                        <span class="text-xs font-bold text-gray-500">Message</span>
                                        <p class="mt-1 text-sm text-gray-600" x-text="detail.message"></p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-xl border border-[#f0dada] bg-gray-50 p-4">
                                <div class="mb-3">
                                    <h4 class="text-base font-extrabold text-gray-900">Required documents and clearances</h4>
                                    <p class="mt-1 text-xs text-gray-500">Enter one requirement per line. These instructions appear in the employee tracker.</p>
                                </div>
                                <form x-show="!detail.requirements_sent && detail.is_current" method="POST" :action="detail.requirements_url" class="grid gap-1.5">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="requirements_text" rows="4" required x-model="detail.requirements_text"
                                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm leading-5 text-gray-800 focus:border-[#b22222] focus:outline-none focus:ring-1 focus:ring-[#b22222]"></textarea>
                                    <button type="submit"
                                        class="min-h-10 w-full cursor-pointer rounded-md bg-[#b22222] px-4 py-2 text-sm font-extrabold text-white transition hover:bg-[#921c1c]">
                                        Send requirements to employee
                                    </button>
                                </form>
                                <p x-show="detail.requirements_sent" class="rounded-md border border-[#f0dada] bg-white px-3 py-2.5 text-sm text-gray-600">
                                    Requirements sent to employee.
                                </p>
                            </section>

                            <section x-show="detail.interviews.length" class="rounded-xl border border-[#f0dada] bg-gray-50 p-4">
                                <h4 class="mb-2 text-base font-extrabold text-gray-900">Interview clearances</h4>
                                <ul class="m-0 grid list-none gap-2 p-0">
                                    <template x-for="interview in detail.interviews" :key="interview.label">
                                        <li class="flex items-center justify-between gap-3 rounded-md border border-[#f0dada] bg-white px-3 py-2.5">
                                            <span class="text-sm font-semibold text-gray-800" x-text="interview.label"></span>
                                            <form method="POST" :action="interview.url" class="m-0">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="label" :value="interview.label">
                                                <button type="submit" :disabled="interview.done || !detail.requirements_sent || !detail.is_current"
                                                    :class="interview.done ? 'bg-green-700' : (detail.requirements_sent && detail.is_current ? 'bg-[#b22222] hover:bg-[#921c1c]' : 'bg-gray-300')"
                                                    class="min-h-8 cursor-pointer rounded-md px-3 py-1.5 text-xs font-bold text-white disabled:cursor-not-allowed"
                                                    x-text="interview.done ? 'Done' : 'Mark done'"></button>
                                            </form>
                                        </li>
                                    </template>
                                </ul>
                            </section>

                            <section class="rounded-xl border border-[#f0dada] bg-gray-50 p-4">
                                <h4 class="mb-2 text-base font-extrabold text-gray-900">Employee-submitted documents</h4>
                                <ul class="m-0 grid list-none gap-2 p-0">
                                    <template x-for="doc in detail.documents" :key="doc.label">
                                        <li class="grid gap-2 rounded-md border border-dashed border-[#f0dada] bg-white px-3 py-2.5">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="grid min-w-0 gap-0.5">
                                                    <strong class="text-sm text-gray-800" x-text="doc.label"></strong>
                                                    <a x-show="doc.url" :href="doc.url" target="_blank" rel="noopener"
                                                        class="break-all text-xs font-semibold text-[#b22222] underline" x-text="doc.name"></a>
                                                    <span x-show="!doc.id" class="text-xs text-gray-500">Awaiting employee upload</span>
                                                    <span x-show="doc.id && !doc.url" class="break-all text-xs text-gray-500" x-text="doc.name + ' (file unavailable)'"></span>
                                                    <span x-show="doc.rejection_reason" class="text-xs text-[#b22222]" x-text="'HR note: ' + doc.rejection_reason"></span>
                                                </div>
                                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-extrabold"
                                                    :class="doc.status === 'Approved' ? 'bg-[#e8f5ec] text-[#21683a]' : (doc.status === 'Rejected' ? 'bg-[#fdeaea] text-[#b22222]' : (doc.id ? 'bg-[#fff5d6] text-[#8a5a00]' : 'bg-gray-100 text-gray-500'))"
                                                    x-text="doc.status"></span>
                                            </div>
                                            <div x-show="doc.id && detail.is_current && doc.status !== 'Approved'" class="flex flex-wrap justify-end gap-2">
                                                <form method="POST" :action="doc.review_url" class="m-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="decision" value="approved">
                                                    <button type="submit" class="min-h-8 cursor-pointer rounded-md bg-green-700 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-green-800 active:bg-green-900">Approve</button>
                                                </form>
                                                <button type="button" @click.stop="openRejection(doc)"
                                                    class="min-h-8 cursor-pointer rounded-md bg-red-700 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-800 active:bg-red-900">
                                                    Reject
                                                </button>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </section>

                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p role="status" class="m-0 text-xs text-gray-500" x-text="detail.status_message"></p>
                                <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                                    <button x-show="detail.can_hr_reject" type="button" @click.stop="openCaseRejection()"
                                        class="min-h-9 cursor-pointer rounded-md bg-red-700 px-4 py-2 text-sm font-extrabold text-white transition hover:bg-red-800">
                                        Reject request
                                    </button>
                                    <form method="POST" :action="detail.forward_url" class="m-0">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" :disabled="!detail.can_forward"
                                            class="min-h-9 w-full cursor-pointer rounded-md bg-[#b22222] px-4 py-2 text-sm font-extrabold text-white disabled:cursor-not-allowed disabled:bg-gray-300 sm:w-auto">
                                            Send to Admin
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="grid gap-4 text-sm">
                            <section class="rounded-xl border border-[#f0dada] bg-gray-50 p-4">
                                <h4 class="mb-2 text-base font-extrabold text-gray-900">Employee request</h4>
                                <div class="grid grid-cols-1 items-start gap-3 rounded-lg border border-[#f0dada] bg-white p-3 sm:grid-cols-[1fr_auto]">
                                    <div class="grid gap-1">
                                        <span class="text-xs font-bold text-gray-500">Last working day</span>
                                        <strong class="text-sm text-gray-800" x-text="detail.last_working_day"></strong>
                                    </div>
                                    <div class="max-w-[420px] text-left sm:text-right">
                                        <span class="text-xs font-bold text-gray-500">Message</span>
                                        <p class="mt-1 text-sm text-gray-600" x-text="detail.message"></p>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-xl border border-[#f0dada] bg-gray-50 p-4">
                                <div class="mb-3">
                                    <h4 class="text-base font-extrabold text-gray-900">Required documents and clearances</h4>
                                    <p class="mt-1 text-xs text-gray-500">Requirements sent to employee.</p>
                                </div>
                                <p class="rounded-md border border-[#f0dada] bg-white px-3 py-2.5 text-sm text-gray-600"
                                    x-text="detail.requirements_text.split('\n').join(', ')"></p>
                            </section>

                            <section x-show="detail.interviews.length" class="rounded-xl border border-[#f0dada] bg-gray-50 p-4">
                                <h4 class="mb-2 text-base font-extrabold text-gray-900">Interview clearances</h4>
                                <ul class="m-0 grid list-none gap-2 p-0">
                                    <template x-for="interview in detail.interviews" :key="interview.label">
                                        <li class="flex items-center justify-between gap-3 rounded-md border border-[#f0dada] bg-white px-3 py-2.5">
                                            <span class="text-sm font-semibold text-gray-800" x-text="interview.label"></span>
                                            <span class="rounded-md px-3 py-1.5 text-xs font-bold text-white"
                                                :class="interview.done ? 'bg-green-700' : 'bg-gray-400'"
                                                x-text="interview.done ? 'Done' : 'Pending'"></span>
                                        </li>
                                    </template>
                                </ul>
                            </section>

                            <section class="rounded-xl border border-[#f0dada] bg-gray-50 p-4">
                                <h4 class="mb-2 text-base font-extrabold text-gray-900">Employee-submitted documents</h4>
                                <ul class="m-0 grid list-none gap-2 p-0">
                                    <template x-for="doc in detail.documents" :key="doc.id">
                                        <li class="grid gap-2 rounded-md border border-dashed border-[#f0dada] bg-white px-3 py-2.5">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="grid min-w-0 gap-0.5">
                                                    <strong class="text-sm text-gray-800" x-text="doc.label"></strong>
                                                    <a x-show="doc.url" :href="doc.url" target="_blank" rel="noopener"
                                                        class="break-all text-xs font-semibold text-[#b22222] underline" x-text="doc.name"></a>
                                                    <span x-show="!doc.url" class="break-all text-xs text-gray-500" x-text="doc.name + ' (file unavailable)'"></span>
                                                </div>
                                                <span class="shrink-0 rounded-full px-3 py-1.5 text-xs font-extrabold"
                                                    :class="doc.hr_ok ? 'bg-[#e8f5ec] text-[#21683a]' : 'bg-[#fff5d6] text-[#8a5a00]'"
                                                    x-text="doc.hr_ok ? 'Approved' : doc.status"></span>
                                            </div>
                                        </li>
                                    </template>
                                    <li x-show="!detail.documents.length" class="rounded-md border border-dashed border-[#f0dada] bg-white px-3 py-2.5 text-sm text-gray-500">
                                        No submitted documents are available.
                                    </li>
                                </ul>
                            </section>

                            <div class="flex flex-col items-stretch justify-between gap-3.5 sm:flex-row sm:items-center">
                                <p role="status" class="m-0 text-gray-500" x-text="detail.status_message"></p>

                                <div x-show="detail.can_decide" class="flex w-full items-center justify-end gap-2 sm:w-auto">
                                    <form method="POST" :action="detail.approve_url" class="m-0"
                                        onsubmit="return confirm('Give final approval to this offboarding case?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" :disabled="!detail.can_approve"
                                            class="min-h-[38px] cursor-pointer rounded-md bg-green-700 px-5 py-2 font-bold text-white transition hover:bg-green-800 disabled:cursor-not-allowed disabled:bg-gray-300"
                                            x-text="detail.approved ? 'Final approved' : 'Approve'"></button>
                                    </form>
                                    <button type="button"
                                        @click.stop="openCaseRejection()"
                                        class="min-h-[38px] cursor-pointer rounded-md bg-red-700 px-5 py-2 font-bold text-white transition hover:bg-red-800 active:bg-red-900">
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </template>
            </section>
        </div>

        {{-- ============ Rejection reason modal ============ --}}
        <div x-show="rejection" x-cloak x-transition.opacity class="fixed inset-0 z-[70] grid place-items-center p-5">
            <div class="absolute inset-0 bg-[rgba(17,24,39,0.65)]" @click="closeRejection()"></div>

            <section role="dialog" aria-modal="true" aria-labelledby="offboardingRejectTitle"
                class="relative w-full max-w-[480px] rounded-[18px] bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.3)] sm:p-6">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="offboardingRejectTitle" class="text-lg font-extrabold text-gray-900">Reason for rejection</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            <span x-text="rejection && rejection.type === 'case' ? 'Explain why this request is being rejected for ' : 'Explain what needs to be corrected for '"></span>
                            <strong class="text-gray-700" x-text="rejection ? rejection.label : ''"></strong>.
                        </p>
                    </div>
                    <button type="button" @click="closeRejection()" aria-label="Close rejection dialog"
                        class="h-8 w-8 shrink-0 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-800 transition hover:bg-gray-200">&times;</button>
                </div>

                <form method="POST" :action="rejection ? rejection.review_url : ''" class="grid gap-3">
                    @csrf
                    @method('PATCH')
                    <input x-show="rejection && rejection.type === 'document'" type="hidden" name="decision" value="rejected">
                    <label for="offboardingRejectReason" class="text-sm font-bold text-gray-800">Rejection reason</label>
                    <textarea id="offboardingRejectReason" name="reason" x-model="rejectionReason"
                        x-ref="rejectionReason" rows="4" maxlength="1000" required
                        :placeholder="rejection && rejection.type === 'case' ? 'Share the reason for rejecting this request.' : 'Describe what the employee needs to replace or correct.'"
                        class="w-full resize-y rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-100"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="closeRejection()"
                            class="min-h-9 cursor-pointer rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" :disabled="!rejectionReason.trim()"
                            class="min-h-9 cursor-pointer rounded-md bg-red-700 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-800 disabled:cursor-not-allowed disabled:bg-gray-300">
                            <span x-text="rejection && rejection.type === 'case' ? 'Reject request' : 'Reject document'"></span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
=======
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    </div>

    <script>
        function offboardingReview(cfg) {
            return {
                items: cfg.items,
<<<<<<< HEAD
                detailId: cfg.openId,
                rejection: null,
                rejectionReason: '',
=======
                detailId: null,
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

                get detail() {
                    return this.detailId ? this.items[this.detailId] : null;
                },

                openDetail(id) {
                    this.detailId = id;
                },
<<<<<<< HEAD

                openRejection(item) {
                    this.rejection = item;
                    this.rejectionReason = '';
                    this.$nextTick(() => this.$refs.rejectionReason?.focus());
                },

                openCaseRejection() {
                    this.openRejection({
                        label: `${this.detail.employee_name}'s offboarding request`,
                        review_url: this.detail.reject_url,
                        type: 'case',
                    });
                },

                closeRejection() {
                    this.rejection = null;
                    this.rejectionReason = '';
                },
            };
        }
    </script>
=======
            };
        }
    </script>

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

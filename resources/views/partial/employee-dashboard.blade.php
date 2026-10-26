{{-- resources/views/admin/onboarding-management/index.blade.php --}}
{{-- Expects: $applications (paginator), $cards (object keyed by id), $stats, $jobOpenings, $search, $status --}}

    <x-slot name="title">
        Onboarding Dashboard
    </x-slot>

    <x-slot name="header">
        <h1 class="ml-20 text-2xl font-extrabold leading-none tracking-tight text-[#b22222] sm:text-4xl">Onboarding Management</h1>
    </x-slot>

    @php
        $badge = fn (string $key) => match ($key) {
            'approved'  => 'bg-[#dcfce7] text-[#166534]',
            'rejected'  => 'bg-[#fee2e2] text-[#991b1b]',
            'scheduled' => 'bg-[#dbeafe] text-[#0369a1]',
            default     => 'bg-[#fef3c7] text-[#b45309]',
        };

        $statCards = [
            ['Total Applicants', $stats['total'],        'All submitted applications'],
            ['Under Review',     $stats['under_review'], 'Pending evaluation'],
            ['Approved',         $stats['approved'],     'Qualified candidates'],
            ['Rejected',         $stats['rejected'],     'Did not meet requirements'],
        ];

        $statusOptions = $tab === 'archive'
            ? [
                ''         => 'All archived applicants',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ]
            : [
                ''          => 'All active applicants',
                'pending'   => 'Under Review',
                'scheduled' => 'Interview Scheduled',
            ];

        $fieldClass = 'rounded-md border border-gray-200 px-3 py-2.5 text-sm font-normal outline-none focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/10';
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="onboardingApplicants(@js(['items' => $cards, 'open' => session('open_application')]))"
        @keydown.escape.window="addingJob ? addingJob = false : (managingJobs ? managingJobs = false : (rejecting ? rejecting = false : closeDetail()))"
    >
        {{-- ============ Stat cards ============ --}}
        <section aria-label="Applicant statistics" class="mt-2 grid max-w-[1100px] gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($statCards as [$label, $value, $description])
                <div class="rounded-[18px] border border-[#f0dada] bg-white px-5 py-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <strong class="block text-3xl font-extrabold leading-none text-gray-900">{{ $value }}</strong>
                    <span class="mt-2 block text-sm font-bold text-gray-900">{{ $label }}</span>
                    <span class="mt-1 block text-xs text-gray-400">{{ $description }}</span>
                </div>
            @endforeach
        </section>

        {{-- ============ Open positions (HR only) ============ --}}
        @if (auth()->user()->hasRole('HR'))
            @include('partial.jobopening')
        @endif

        {{-- ============ Applications ============ --}}
        <section aria-labelledby="applicantQueueTitle"
            class="mt-6 max-w-[1100px] rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">

            <div class="mb-6">
                <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Applicants</p>
                <h2 id="applicantQueueTitle" class="flex items-center gap-3 text-2xl font-bold text-gray-900">
                    <i class="fas fa-address-book text-[#dc2626]" aria-hidden="true"></i>
                    {{ $tab === 'archive' ? 'Archived applications' : 'Submitted applications' }}
                </h2>
                <p class="mt-2 text-gray-500">
                    {{ $tab === 'archive' ? 'Approved and rejected applicants are automatically moved here.' : 'Manage and review active applicant submissions.' }}
                </p>
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

            {{-- Active / Archive tabs --}}
            <nav aria-label="Applicant application status" class="mb-5 flex gap-2 border-b border-gray-200">
                <a href="{{ route('onboarding.index', ['tab' => 'active', 'q' => $search]) }}"
                    @class([
                        'border-b-2 px-4 py-3 text-sm font-semibold transition',
                        'border-[#b22222] text-[#b22222]' => $tab === 'active',
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800' => $tab !== 'active',
                    ])
                    aria-current="{{ $tab === 'active' ? 'page' : 'false' }}">
                    Active <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $counts['active'] }}</span>
                </a>
                <a href="{{ route('onboarding.index', ['tab' => 'archive', 'q' => $search]) }}"
                    @class([
                        'border-b-2 px-4 py-3 text-sm font-semibold transition',
                        'border-[#b22222] text-[#b22222]' => $tab === 'archive',
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800' => $tab !== 'archive',
                    ])
                    aria-current="{{ $tab === 'archive' ? 'page' : 'false' }}">
                    Archive <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $counts['archive'] }}</span>
                </a>
            </nav>

            {{-- Search + status filter --}}
            <form method="GET" action="{{ route('onboarding.index') }}" class="mb-5 flex flex-col gap-3 sm:flex-row">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label class="flex flex-1 items-center gap-3 rounded-lg border border-gray-200 bg-white px-5 py-3 shadow-sm focus-within:border-[#dc2626] focus-within:ring-2 focus-within:ring-[#dc2626]/10">
                    <i class="fas fa-search text-gray-400" aria-hidden="true"></i>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Search by name, email, or ID..."
                        aria-label="Search applicants"
                        class="flex-1 border-0 bg-transparent p-0 text-[15px] text-gray-800 outline-none placeholder:text-gray-400 focus:ring-0">
                </label>

                <select name="status" aria-label="Filter by application status" onchange="this.form.submit()"
                    class="cursor-pointer rounded-lg border border-gray-200 bg-white px-4 py-3 text-[15px] text-gray-800 shadow-sm outline-none focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/10 sm:w-52">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>

            {{-- Table --}}
            @if ($applications->isNotEmpty())
                <div class="overflow-x-auto rounded-[14px] border border-[#ecebea]">
                    <table class="w-full border-collapse bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach (['Applicant ID', 'Name & Email', 'Position Applied', 'Job Type', 'Contact', 'Status', 'Actions'] as $heading)
                                    <th class="whitespace-nowrap border-b border-gray-200 px-5 py-4 text-center text-xs font-bold uppercase tracking-[0.05em] text-gray-900">{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($applications as $application)
                                <tr @click="openDetail({{ $application->id }})"
                                    class="cursor-pointer transition-colors duration-200 hover:bg-gray-50">

                                    <td class="whitespace-nowrap border-b border-gray-100 px-5 py-4 text-center text-sm text-gray-800">{{ $application->code }}</td>

                                    <td class="border-b border-gray-100 px-5 py-4 text-center">
                                        <strong class="block break-words text-[15px] font-bold text-slate-900">{{ $application->fullname }}</strong>
                                        <span class="mt-1 block break-words text-[13px] text-gray-500">{{ $application->email }}</span>
                                    </td>

                                    <td class="border-b border-gray-100 px-5 py-4 text-center text-sm text-gray-800">{{ $application->position }}</td>
                                    <td class="border-b border-gray-100 px-5 py-4 text-center text-sm text-gray-800">{{ $application->job_type ?: 'Staff' }}</td>
                                    <td class="whitespace-nowrap border-b border-gray-100 px-5 py-4 text-center text-sm text-gray-800">{{ $application->phone ?: 'N/A' }}</td>

                                    <td class="border-b border-gray-100 px-5 py-4 text-center">
                                        <span class="inline-flex items-center whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-semibold {{ $badge($application->status_key) }}">
                                            {{ $application->workflow_status }}
                                        </span>
                                    </td>

                                    <td class="border-b border-gray-100 px-5 py-4 text-center">
                                        <form method="POST" action="{{ route('onboarding.destroy', $application) }}"
                                            class="m-0 inline-block" @click.stop
                                            onsubmit="return confirm('Are you sure you want to delete this applicant?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" aria-label="Delete applicant" title="Delete"
                                                class="inline-flex h-8 cursor-pointer items-center justify-center rounded-md border border-gray-200 bg-white px-3 text-sm font-semibold text-[#dc2626] transition hover:border-red-300 hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($applications->hasPages())
                    <div class="mt-5">{{ $applications->links() }}</div>
                @endif
            @else
                <div class="flex flex-col items-center gap-4 rounded-[14px] border border-dashed border-[#e5caca] px-10 py-14 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-300" aria-hidden="true"></i>
                    @if ($search !== '' || $status !== '')
                        <h3 class="text-xl font-bold text-gray-800">No matching applications</h3>
                        <p class="max-w-md text-gray-500">Try a different search or status filter.</p>
                    @elseif ($tab === 'archive')
                        <h3 class="text-xl font-bold text-gray-800">Archive is empty</h3>
                        <p class="max-w-md text-gray-500">Approved and rejected applications will appear here automatically.</p>
                    @else
                        <h3 class="text-xl font-bold text-gray-800">No Applications Yet</h3>
                        <p class="max-w-md text-gray-500">Once candidates apply, their information will appear here.</p>
                    @endif
                </div>
            @endif
        </section>

        {{-- ============ Applicant details modal ============ --}}
        <div x-show="detail" x-cloak x-transition.opacity class="fixed inset-0 z-[60] grid place-items-center p-5">
            <div class="absolute inset-0 bg-[rgba(17,24,39,0.55)]" @click="closeDetail()"></div>

            <section role="dialog" aria-modal="true" aria-labelledby="applicantModalTitle"
                class="relative max-h-[90vh] w-full max-w-[980px] overflow-auto rounded-[20px] bg-white p-[26px] shadow-[0_30px_80px_rgba(15,23,42,0.25)]">

                <div class="mb-[22px] flex items-start justify-between gap-5">
                    <div class="min-w-0">
                        <h2 id="applicantModalTitle" class="text-[1.5rem] font-bold" x-text="detail ? detail.full_name : ''"></h2>
                        <p class="mt-1 text-sm text-gray-500" x-text="detail ? detail.position + ' · ' + detail.code : ''"></p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="whitespace-nowrap rounded-full px-4 py-2 text-xs font-semibold"
                            :class="detail ? statusClasses[detail.status_key] : ''"
                            x-text="detail ? detail.status : ''"></span>
                        <button type="button" @click="closeDetail()" aria-label="Close details"
                            class="h-9 w-9 shrink-0 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
                    </div>
                </div>

                {{-- Result of the last action (shown when the page reloads with this modal open) --}}
                <div x-show="reopened" class="mb-5 grid gap-2">
                    @if (session('success'))
                        <div class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>

                <template x-if="detail">
                    <div class="grid gap-6">
                        <div class="grid gap-6 md:grid-cols-[minmax(0,2fr)_minmax(220px,1fr)]">
                            {{-- Contact details --}}
                            <div>
                                <h3 class="mb-3 text-base font-bold text-slate-900">Contact details</h3>
                                <div class="grid gap-x-4 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                                    <p class="min-w-0 text-sm">
                                        <strong class="block text-[0.78rem] uppercase tracking-[0.04em] text-gray-500">Email</strong>
                                        <a :href="'mailto:' + detail.email" class="break-words text-[#b22222] underline" x-text="detail.email"></a>
                                    </p>
                                    @foreach ([
                                        ['Phone', 'phone'],
                                        ['Home address', 'address'],
                                        ['Birth date', 'birth_date'],
                                        ['Gender', 'gender'],
                                        ['Civil status', 'civil_status'],
                                        ['Highest education', 'education'],
                                        ['Submitted', 'submitted_at'],
                                        ['Job type', 'job_type'],
                                    ] as [$label, $key])
                                        <p class="min-w-0 text-sm">
                                            <strong class="block text-[0.78rem] uppercase tracking-[0.04em] text-gray-500">{{ $label }}</strong>
                                            <span class="break-words text-gray-700" x-text="detail.{{ $key }}"></span>
                                        </p>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Interview details --}}
                            <div>
                                <h3 class="mb-3 text-base font-bold text-slate-900">Interview details</h3>
                                <div class="grid gap-3">
                                    @foreach ([
                                        ['Date', 'interview_date'],
                                        ['Time', 'interview_time'],
                                        ['Location', 'interview_location'],
                                    ] as [$label, $key])
                                        <p class="min-w-0 text-sm">
                                            <strong class="block text-[0.78rem] uppercase tracking-[0.04em] text-gray-500">{{ $label }}</strong>
                                            <span class="break-words text-gray-700" x-text="detail.{{ $key }}"></span>
                                        </p>
                                    @endforeach

                                    <p x-show="detail.interview_note" class="min-w-0 text-sm">
                                        <strong class="block text-[0.78rem] uppercase tracking-[0.04em] text-gray-500">Note to applicant</strong>
                                        <span class="whitespace-pre-line break-words text-gray-700" x-text="detail.interview_note"></span>
                                    </p>

                                    <p x-show="detail.rejection_reason" class="min-w-0 text-sm">
                                        <strong class="block text-[0.78rem] uppercase tracking-[0.04em] text-[#b22222]">Rejection reason</strong>
                                        <span class="break-words text-gray-700" x-text="detail.rejection_reason"></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Uploaded documents --}}
                        <div>
                            <h3 class="mb-3 text-base font-bold text-slate-900">Uploaded documents</h3>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <template x-for="doc in detail.documents" :key="doc.label">
                                    <div class="min-w-0">
                                        <a x-show="doc.url" :href="doc.url" target="_blank" rel="noopener"
                                            class="inline-flex w-full min-w-0 items-center gap-3 break-words rounded-[10px] border border-[#dc2626] bg-[#fef2f2] px-4 py-3 text-sm font-extrabold text-[#dc2626] transition hover:bg-[#fdecea]">
                                            <i class="fas fa-file" aria-hidden="true"></i>
                                            <span class="min-w-0 break-all" x-text="doc.name || doc.label"></span>
                                        </a>
                                        <span x-show="!doc.url" class="inline-flex items-center gap-2 py-2.5 text-sm text-gray-400">
                                            <i class="fas fa-times" aria-hidden="true"></i>
                                            <span x-text="doc.label + ' not provided'"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Schedule interview --}}
                        <div x-show="detail.can_schedule">
                            <h3 class="mb-3 text-base font-bold text-slate-900"
                                x-text="detail.status_key === 'scheduled' ? 'Reschedule interview' : 'Schedule interview'"></h3>

                            <form method="POST" :action="detail.schedule_url" class="grid gap-3 sm:grid-cols-2">
                                @csrf

                                <label class="grid gap-1 text-sm font-semibold text-gray-800">
                                    Scheduled date
                                    <input type="date" name="interview_date" required
                                        min="{{ now()->toDateString() }}"
                                        :value="detail.interview_date_value"
                                        class="{{ $fieldClass }}">
                                </label>

                                <label class="grid gap-1 text-sm font-semibold text-gray-800">
                                    Scheduled time <span class="text-xs font-normal text-gray-400">(8:00 AM – 2:00 PM)</span>
                                    <input type="time" name="interview_time" required min="08:00" max="14:00"
                                        :value="detail.interview_time_value"
                                        class="{{ $fieldClass }}">
                                </label>

                                <label class="grid gap-1 text-sm font-semibold text-gray-800 sm:col-span-2">
                                    Location
                                    <input type="text" name="interview_location" maxlength="255"
                                        placeholder="e.g. HR Office, 2nd floor"
                                        :value="detail.interview_location !== 'N/A' ? detail.interview_location : ''"
                                        class="{{ $fieldClass }}">
                                    <span class="text-xs font-normal text-gray-400">Leave blank to use St. Mary Magdalene School.</span>
                                </label>

                                <label class="grid gap-1 text-sm font-semibold text-gray-800 sm:col-span-2">
                                    Note
                                    <textarea name="interview_note" rows="2" maxlength="1000"
                                        placeholder="Optional message for applicant"
                                        :value="detail.interview_note"
                                        class="resize-y {{ $fieldClass }}"></textarea>
                                </label>

                                <div class="sm:col-span-2">
                                    <button type="submit"
                                        class="cursor-pointer rounded-lg bg-[#dc2626] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#b91c1c]"
                                        x-text="detail.status_key === 'scheduled' ? 'Update schedule' : 'Schedule interview'"></button>
                                </div>
                            </form>
                        </div>

                        {{-- Approve / Reject (enabled once the interview time has passed) --}}
                        <div>
                            <p x-show="!detail.can_decide && detail.status_key !== 'approved' && detail.status_key !== 'rejected'"
                                class="mb-3 text-sm text-gray-500">
                                <span x-show="detail.status_key === 'scheduled'">Decision is available once the scheduled interview time has passed.</span>
                                <span x-show="detail.status_key !== 'scheduled'">Schedule an interview first. A decision can be made after it is done.</span>
                            </p>

                            <div class="flex flex-wrap justify-between gap-4">
                                <form method="POST" :action="detail.decide_url" class="m-0"
                                    onsubmit="return confirm('Approve this application?');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="decision" value="approved">
                                    <button type="submit" :disabled="!detail.can_decide"
                                        class="cursor-pointer rounded-[10px] bg-[#15803d] px-[18px] py-[11px] font-extrabold text-white transition hover:-translate-y-px hover:brightness-95 hover:shadow-[0_8px_18px_rgba(15,23,42,0.12)] disabled:cursor-not-allowed disabled:opacity-55 disabled:hover:translate-y-0 disabled:hover:shadow-none">
                                        Approve application
                                    </button>
                                </form>

                                <button type="button" @click="rejecting = true" :disabled="!detail.can_decide"
                                    class="cursor-pointer rounded-[10px] bg-[#b22222] px-[18px] py-[11px] font-extrabold text-white transition hover:-translate-y-px hover:brightness-95 hover:shadow-[0_8px_18px_rgba(15,23,42,0.12)] disabled:cursor-not-allowed disabled:opacity-55 disabled:hover:translate-y-0 disabled:hover:shadow-none">
                                    Reject application
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </section>
        </div>

        {{-- ============ Rejection reason modal ============ --}}
        <div x-show="rejecting && detail" x-cloak x-transition.opacity class="fixed inset-0 z-[70] grid place-items-center p-5">
            <div class="absolute inset-0 bg-[rgba(17,24,39,0.7)]" @click="rejecting = false"></div>

            <section role="dialog" aria-modal="true" aria-labelledby="rejectReasonTitle"
                class="relative w-full max-w-[520px] rounded-[20px] bg-white p-[26px] shadow-[0_30px_90px_rgba(0,0,0,0.3)]">

                <div class="mb-4 flex items-start justify-between gap-5">
                    <div>
                        <h2 id="rejectReasonTitle" class="text-xl font-bold">Reason for rejection</h2>
                        <p class="mt-1 text-sm text-gray-500">Provide a reason before rejecting this application.</p>
                    </div>
                    <button type="button" @click="rejecting = false" aria-label="Close rejection reason dialog"
                        class="h-9 w-9 shrink-0 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
                </div>

                <form method="POST" :action="detail ? detail.decide_url : ''" class="grid gap-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="decision" value="rejected">

                    <label for="rejectReasonInput" class="text-sm font-bold text-gray-800">Rejection reason</label>
                    <textarea id="rejectReasonInput" name="reason" rows="5" required maxlength="1000"
                        placeholder="Enter the reason for rejecting this application"
                        x-ref="reason"
                        x-effect="if (rejecting) $nextTick(() => { $refs.reason.value = ''; $refs.reason.focus(); })"
                        class="w-full resize-y rounded-md border border-gray-200 p-3 text-sm leading-normal outline-none focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/10"></textarea>

                    <div class="mt-1 flex justify-end gap-3">
                        <button type="button" @click="rejecting = false"
                            class="cursor-pointer rounded-lg bg-gray-100 px-5 py-3 font-bold text-gray-800 transition hover:bg-gray-200">
                            Cancel
                        </button>
                        <button type="submit"
                            class="cursor-pointer rounded-lg bg-[#ef4444] px-5 py-3 font-bold text-white transition hover:bg-[#dc2626]">
                            Reject application
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        function onboardingApplicants(cfg) {
            // If the page reloaded after a schedule/decision action, reopen that applicant's modal
            const reopenId = cfg.open && cfg.items[cfg.open] ? Number(cfg.open) : null;

            return {
                items: cfg.items,
                detailId: reopenId,
                reopened: reopenId !== null, // shows the success/error banner inside the modal
                rejecting: false,
                managingJobs: false,         // job openings list modal
                addingJob: false,            // add job opening modal

                statusClasses: {
                    pending:   'bg-[#fef3c7] text-[#b45309]',
                    scheduled: 'bg-[#dbeafe] text-[#0369a1]',
                    approved:  'bg-[#dcfce7] text-[#166534]',
                    rejected:  'bg-[#fee2e2] text-[#991b1b]',
                },

                get detail() {
                    return this.detailId ? this.items[this.detailId] : null;
                },

                openDetail(id) {
                    this.rejecting = false;
                    this.reopened = false;
                    this.detailId = id;
                },

                closeDetail() {
                    this.detailId = null;
                    this.reopened = false;
                },
            };
        }
    </script>
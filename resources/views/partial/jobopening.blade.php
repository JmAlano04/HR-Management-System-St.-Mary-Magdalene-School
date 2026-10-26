{{-- Expects: $jobOpenings (collection of ['id','title','applicants']) --}}
{{-- Must be included inside the onboardingApplicants x-data scope (uses managingJobs, addingJob) --}}
<section aria-labelledby="openPositionsTitle"
    class="mt-6 max-w-[1100px] rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">

    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 id="openPositionsTitle" class="flex items-center gap-3 text-2xl font-extrabold tracking-wide text-slate-900">
                <i class="fas fa-briefcase text-2xl text-[#dc2626]" aria-hidden="true"></i>
                Open Positions
            </h2>
            <p class="mt-1.5 text-sm font-medium text-gray-500">
                {{ $jobOpenings->isEmpty()
                    ? 'No open positions available.'
                    : $jobOpenings->count() . ' position' . ($jobOpenings->count() !== 1 ? 's' : '') . ' available' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" @click="managingJobs = true"
                class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-[18px] py-2.5 text-sm font-semibold text-gray-800 transition hover:bg-gray-50">
                <i class="fas fa-list" aria-hidden="true"></i> Job Openings
            </button>
            <button type="button" @click="addingJob = true"
                class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-[#dc2626] px-[18px] py-2.5 text-sm font-semibold text-white shadow-[0_2px_6px_rgba(220,38,38,0.2)] transition hover:bg-[#b91c1c]">
                <i class="fas fa-plus-circle" aria-hidden="true"></i> Add Job Opening
            </button>
        </div>
    </div>

    @if ($jobOpenings->isNotEmpty())
        <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
            @foreach ($jobOpenings as $job)
                <div class="min-w-0 rounded-[10px] border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-semibold leading-snug text-slate-900">{{ $job['title'] }}</h3>
                    <div class="mt-2 text-[22px] font-bold text-[#dc2626]">{{ $job['applicants'] }}</div>
                    <p class="mt-1 text-xs text-gray-500">Applicant{{ $job['applicants'] !== 1 ? 's' : '' }} applied</p>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Reopen the add modal if adding failed validation (e.g. duplicate title) --}}
    @if ($errors->has('title'))
        <div x-init="addingJob = true" class="hidden"></div>
    @endif

    {{-- ============ Add job opening modal ============ --}}
    <div x-show="addingJob" x-cloak x-transition.opacity
        x-effect="if (addingJob) $nextTick(() => $refs.newJobTitle && $refs.newJobTitle.focus())"
        class="fixed inset-0 z-[70] grid place-items-center p-5">
        <div class="absolute inset-0 bg-[rgba(17,24,39,0.6)]" @click="addingJob = false"></div>

        <section role="dialog" aria-modal="true" aria-labelledby="addJobTitle"
            class="relative w-full max-w-[460px] rounded-[20px] bg-white p-[26px] shadow-[0_30px_90px_rgba(0,0,0,0.3)]">

            <div class="mb-4 flex items-start justify-between gap-5">
                <div>
                    <h2 id="addJobTitle" class="text-xl font-bold">Add job opening</h2>
                    <p class="mt-1 text-sm text-gray-500">Enter the title of the new position.</p>
                </div>
                <button type="button" @click="addingJob = false" aria-label="Close add job opening dialog"
                    class="h-9 w-9 shrink-0 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
            </div>

            <form method="POST" action="{{ route('job-openings.store') }}" class="grid gap-3">
                @csrf

                <label for="newJobTitle" class="text-sm font-bold text-gray-800">Position title</label>
                <input type="text" id="newJobTitle" name="title" x-ref="newJobTitle" required maxlength="255"
                    value="{{ old('title') }}" placeholder="e.g. Math Teacher"
                    class="w-full rounded-md border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-[#dc2626] focus:ring-2 focus:ring-[#dc2626]/10">

                @error('title')
                    <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror

                <div class="mt-1 flex justify-end gap-3">
                    <button type="button" @click="addingJob = false"
                        class="cursor-pointer rounded-lg bg-gray-100 px-5 py-2.5 font-bold text-gray-800 transition hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                        class="cursor-pointer rounded-lg bg-[#dc2626] px-5 py-2.5 font-bold text-white transition hover:bg-[#b91c1c]">
                        Add job opening
                    </button>
                </div>
            </form>
        </section>
    </div>

    {{-- ============ Manage job openings modal (list + delete) ============ --}}
    <div x-show="managingJobs" x-cloak x-transition.opacity class="fixed inset-0 z-[60] grid place-items-center p-5">
        <div class="absolute inset-0 bg-[rgba(17,24,39,0.55)]" @click="managingJobs = false"></div>

        <section role="dialog" aria-modal="true" aria-labelledby="manageJobsTitle"
            class="relative max-h-[90vh] w-full max-w-[520px] overflow-auto rounded-[20px] bg-white p-[26px] shadow-[0_30px_80px_rgba(15,23,42,0.25)]">

            <div class="mb-4 flex items-start justify-between gap-5">
                <div>
                    <h2 id="manageJobsTitle" class="text-xl font-bold">Job openings</h2>
                    <p class="mt-1 text-sm text-gray-500">Review or remove existing positions.</p>
                </div>
                <button type="button" @click="managingJobs = false" aria-label="Close"
                    class="h-9 w-9 shrink-0 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
            </div>

            <button type="button" @click="addingJob = true"
                class="mb-4 inline-flex cursor-pointer items-center gap-2 rounded-lg bg-[#dc2626] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#b91c1c]">
                <i class="fas fa-plus" aria-hidden="true"></i> Add new
            </button>

            @if ($jobOpenings->isNotEmpty())
                <ul class="grid gap-2">
                    @foreach ($jobOpenings as $job)
                        <li class="flex items-center justify-between gap-3 rounded-[10px] border border-gray-200 px-4 py-2.5">
                            <div class="min-w-0">
                                <span class="block break-words text-sm font-semibold text-slate-900">{{ $job['title'] }}</span>
                                <span class="text-xs text-gray-500">{{ $job['applicants'] }} applicant{{ $job['applicants'] !== 1 ? 's' : '' }}</span>
                            </div>

                            <form method="POST" action="{{ route('job-openings.destroy', $job['id']) }}" class="m-0 shrink-0"
                                onsubmit="return confirm(@js($job['applicants'] > 0
                                    ? 'This position has ' . $job['applicants'] . ' applicant(s). Their applications are kept. Remove the opening anyway?'
                                    : 'Remove this job opening?'));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="Remove {{ $job['title'] }}"
                                    class="cursor-pointer rounded-md border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-[#dc2626] transition hover:border-red-300 hover:bg-red-50">
                                    Delete
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="rounded-[10px] border border-dashed border-[#e5caca] px-4 py-6 text-center text-sm text-gray-500">
                    No job openings yet. Click "Add new" to create the first one.
                </p>
            @endif
        </section>
    </div>
</section>
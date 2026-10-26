{{-- resources/views/admin/onboarding-management/partials/open-positions.blade.php --}}
{{-- Expects: $jobOpenings (collection of ['id','title','applicants']) --}}
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

        <button type="button" @click="managingJobs = true"
            class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-[#dc2626] px-[18px] py-2.5 text-sm font-semibold text-white shadow-[0_2px_6px_rgba(220,38,38,0.2)] transition hover:bg-[#b91c1c]">
            <i class="fas fa-plus-circle" aria-hidden="true"></i> Job Openings
        </button>
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
</section>
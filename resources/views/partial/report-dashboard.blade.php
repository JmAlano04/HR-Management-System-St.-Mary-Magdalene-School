{{-- resources/views/admin/reports/index.blade.php --}}

    {{-- Browser tab title --}}
    <x-slot name="title">
        Reports
    </x-slot>

    {{-- Page heading (shown in the layout's top bar) --}}
    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none  ml-20 tracking-tight text-[#b22222] sm:text-4xl">Reports</h1>
    </x-slot>

    @php
        // Link to a module page, or "#" if that route isn't defined yet
        $link = fn (string $name) => \Illuminate\Support\Facades\Route::has($name) ? route($name) : '#';

        // [title, icon, summary numbers, link]
        $sections = [
            ['Employees',               'fa-users',                 $employees,       $link('admin.employees.index')],
            ['Time corrections',        'fa-clock',                 $timeCorrections, $link('time-corrections.index')],
            ['Leave requests',          'fa-calendar-days',         $leave,           $link('admin.leave.index')],
            ['Onboarding',              'fa-user-plus',             $onboarding,      $link('admin.onboarding.index')],
            ['Financial loans',         'fa-hand-holding-dollar',   $loans,           $link('admin.loans.index')],
            ['Offboarding',             'fa-user-slash',            $offboarding,     $link('admin.offboarding.index')],
        ];
    @endphp

    <div class="px-4 pb-12 sm:px-6 lg:px-10">
        <section aria-labelledby="reportsTitle"
            class="mt-2 max-w-[1100px] rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">

            <div class="mb-6">
                <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Overview</p>
                <h2 id="reportsTitle" class="text-2xl font-bold text-gray-900">System reports</h2>
                <p class="mt-2 text-gray-500">A summary of every module. Select a section to open its page.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($sections as [$title, $icon, $numbers, $href])
                    <article class="rounded-[16px] border border-[#ecebea] bg-white p-5 transition duration-200 hover:border-[#d9a5a5] hover:shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="grid h-[42px] w-[42px] place-items-center rounded-lg bg-[#fef2f2] text-[#b22222]">
                                    <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">{{ $title }}</h3>
                            </div>

                            @if ($href !== '#')
                                <a href="{{ $href }}" class="text-sm font-bold text-[#b22222] hover:underline">
                                    Open <i class="fa-solid fa-chevron-right text-[0.65rem]" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>

                        <dl class="m-0 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($numbers as $label => $value)
                                <div class="rounded-xl bg-gray-50 px-3.5 py-3">
                                    <dd class="m-0 text-2xl font-extrabold text-gray-900">{{ $value }}</dd>
                                    <dt class="mt-0.5 text-xs font-bold text-gray-500">{{ $label }}</dt>
                                </div>
                            @endforeach

                            {{-- Extra figure for loans --}}
                            @if ($title === 'Financial loans')
                                <div class="col-span-2 rounded-xl bg-[#fef2f2] px-3.5 py-3 sm:col-span-3">
                                    <dd class="m-0 text-2xl font-extrabold text-[#b22222]">₱{{ number_format($approvedLoanTotal, 2) }}</dd>
                                    <dt class="mt-0.5 text-xs font-bold text-gray-500">Total approved loan amount</dt>
                                </div>
                            @endif
                        </dl>
                    </article>
                @endforeach
            </div>
        </section>
    </div>


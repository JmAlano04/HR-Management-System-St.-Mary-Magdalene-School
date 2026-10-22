{{-- resources/views/reports/index.blade.php --}}

    <x-slot name="title">
        Reports
    </x-slot>

    <x-slot name="header">
        <h1 class="text-2xl font-extrabold leading-none ml-[80px] tracking-tight text-[#b22222] sm:text-4xl">Reports</h1>
    </x-slot>

    @php
        $peso = fn ($n) => '₱' . number_format((float) $n, 2);
        $select = 'w-full rounded-xl border border-[#e7e9ed] bg-white px-3 py-2.5 text-[0.9rem] font-semibold text-[#252d3b] focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10';
        $tabMeta = [
            'hr'         => ['label' => 'HR Reports',         'icon' => 'fa-users'],
            'accounting' => ['label' => 'Accounting Reports', 'icon' => 'fa-money-bill-trend-up'],
        ];
    @endphp

    

    {{-- One GET form: changing any filter reloads the page with that month/year, keeping the open tab --}}
    <form method="GET" action="{{ route('reports.index') }}" x-ref="form"
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="{ tab: @js($tab), tabs: @js($tabs),
            move(e, i) {
                const keys = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: this.tabs.length - 1 };
                if (!(e.key in keys)) return;
                e.preventDefault();
                this.tab = this.tabs[(keys[e.key] + this.tabs.length) % this.tabs.length];
                this.$nextTick(() => document.getElementById(this.tab + 'ReportTab')?.focus());
            } }">
        <input type="hidden" name="tab" :value="tab">

        {{-- Tabs (only shown when the role can see more than one report) --}}
        @if (count($tabs) > 1)
            <div class="mb-5 flex">
                <div role="tablist" aria-label="Report department"
                    class="inline-flex flex-wrap gap-2 rounded-[14px] border border-[#ecebea] bg-white p-1.5 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    @foreach ($tabs as $i => $t)
                        <button type="button" role="tab" id="{{ $t }}ReportTab"
                            aria-controls="{{ $t }}ReportPanel"
                            :aria-selected="tab === '{{ $t }}'" :tabindex="tab === '{{ $t }}' ? 0 : -1"
                            @click="tab = '{{ $t }}'" @keydown="move($event, {{ $i }})"
                            class="inline-flex min-h-[44px] cursor-pointer items-center justify-center gap-[9px] rounded-[10px] px-[18px] py-2.5 text-[0.9rem] font-bold transition"
                            :class="tab === '{{ $t }}' ? 'bg-[#b22222] text-white shadow-[0_4px_12px_rgba(178,34,34,0.2)]' : 'text-gray-500 hover:bg-[#fff5f5] hover:text-[#7f1d1d]'">
                            <i class="fa-solid {{ $tabMeta[$t]['icon'] }}" aria-hidden="true"></i>
                            {{ $tabMeta[$t]['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ============ HR report ============ --}}
        @if ($hr)
            <section id="hrReportPanel" role="tabpanel" aria-labelledby="hrReportTab" x-show="tab === 'hr'" @if ($tab !== 'hr') x-cloak @endif>

                <div class="mb-[22px] grid gap-[18px] md:grid-cols-3" aria-label="Attendance totals for the selected month">
                    @foreach ([
                        ['DAYS WORKED',   $hr['counts']['worked'], 'text-[#151c2b]'],
                        ['LATE ARRIVALS', $hr['counts']['late'],   'text-[#d28a1f]'],
                        ['ABSENCES',      $hr['counts']['absent'], 'text-[#c92e2e]'],
                    ] as [$label, $value, $color])
                        <article class="grid min-h-[150px] content-start gap-2.5 rounded-[28px] border border-[#e5e7ebb8] bg-white px-7 py-[25px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                            <span class="text-[0.82rem] font-extrabold tracking-[0.075em] text-[#737d8e]">{{ $label }}</span>
                            <strong class="text-[clamp(2.1rem,3vw,2.65rem)] font-extrabold leading-none tracking-[-0.055em] {{ $color }}">{{ $value }}</strong>
                            <span class="text-[0.86rem] text-[#8791a1]">{{ $hr['period'] }}</span>
                        </article>
                    @endforeach
                </div>

                <article class="min-h-[570px] rounded-[28px] border border-[#e5e7ebb8] bg-white p-7 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <div class="flex flex-col items-start justify-between gap-6 sm:flex-row">
                        <div>
                            <p class="text-[0.82rem] font-extrabold tracking-[0.075em] text-[#737d8e]">Human resources</p>
                            <h2 class="mt-2.5 text-[clamp(1.65rem,2.2vw,2.15rem)] font-bold leading-tight tracking-[-0.045em] text-[#151c2b]">Monthly Attendance Summary</h2>
                        </div>

                        <div class="flex w-full gap-3 sm:w-auto" aria-label="Choose attendance report period">
                            <label class="grid min-w-0 flex-1 gap-1.5 text-[0.82rem] font-bold text-[#737d8e] sm:min-w-[142px]">
                                Month
                                <select name="hr_month" class="{{ $select }}" @change="$refs.form.submit()">
                                    @foreach (range(1, 12) as $m)
                                        <option value="{{ $m }}" @selected($hr['month'] === $m)>{{ \Illuminate\Support\Carbon::create(2000, $m, 1)->format('F') }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid min-w-0 flex-1 gap-1.5 text-[0.82rem] font-bold text-[#737d8e] sm:min-w-[142px]">
                                Year
                                <select name="hr_year" class="{{ $select }}" @change="$refs.form.submit()">
                                    @foreach ($hr['years'] as $y)
                                        <option value="{{ $y }}" @selected($hr['year'] === $y)>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>

                    <p class="mt-5 flex items-center gap-2 text-[0.84rem] text-[#7a8492]">
                        <a href="{{ route('attendance.index') }}" class="ml-auto inline-flex items-center gap-1.5 font-bold text-[#8f241f] hover:underline">
                            Open attendance records <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </p>

                    @if ($hr['recordCount'] === 0)
                        <p class="mt-4 rounded-xl border border-[#e4e8ee] bg-slate-50 px-3.5 py-3 text-[0.9rem] text-[#596577]" role="status">
                            No attendance records for {{ $hr['period'] }}. Choose another month or add records in Attendance Monitoring.
                        </p>
                    @endif

                    <div class="grid min-h-[420px] items-center gap-8 px-[5.5%] pb-2 pt-6 md:grid-cols-[minmax(240px,0.85fr)_minmax(300px,1.15fr)]">
                        <div>
                            <h3 class="mb-[22px] text-[clamp(1.45rem,2.1vw,1.9rem)] font-bold tracking-[-0.035em] text-[#151c2b]">{{ $hr['period'] }}</h3>
                            @foreach ([['Days worked', $hr['counts']['worked'], 'bg-[#2fbd80]'], ['Late arrivals', $hr['counts']['late'], 'bg-[#e3a33c]'], ['Absences', $hr['counts']['absent'], 'bg-[#e85858]']] as [$l, $v, $dot])
                                <p class="mb-3.5 flex items-center gap-[9px] text-base font-semibold text-[#586273]">
                                    <span class="h-[9px] w-[9px] shrink-0 rounded-full {{ $dot }}"></span>{{ $l }}
                                    <strong class="ml-auto text-[1.05rem] font-extrabold text-[#192131]">{{ $v }}</strong>
                                </p>
                            @endforeach
                            <p class="mt-[22px] max-w-[300px] text-[0.82rem] leading-relaxed text-[#8791a1]">
                                {{ $hr['total'] }} attendance record{{ $hr['total'] === 1 ? '' : 's' }} counted for this period.
                            </p>
                        </div>

                        <div class="min-w-0" role="img"
                            aria-label="{{ $hr['total'] ? "{$hr['period']}: {$hr['counts']['worked']} days worked, {$hr['counts']['late']} late arrivals, {$hr['counts']['absent']} absences" : "No attendance records for {$hr['period']}" }}">
                            @php $hrMax = max(1, $hr['counts']['worked'], $hr['counts']['late'], $hr['counts']['absent']); @endphp
                            <div class="mx-auto flex w-[min(100%,460px)] items-end justify-around gap-6 border-b border-[#e5e7eb] px-4">
                                @foreach ([['Days worked', $hr['counts']['worked'], 'from-[#5fd8a4] to-[#2fbd80]'], ['Late', $hr['counts']['late'], 'from-[#f1c16b] to-[#e3a33c]'], ['Absences', $hr['counts']['absent'], 'from-[#f28b8b] to-[#e85858]']] as [$l, $v, $grad])
                                    <div class="flex flex-1 flex-col items-center gap-2">
                                        <span class="text-[0.95rem] font-extrabold text-[#192131]">{{ $v }}</span>
                                        <div class="flex h-[260px] w-full items-end justify-center">
                                            <div class="w-full max-w-[84px] rounded-t-xl bg-gradient-to-t {{ $grad }} transition-[height] duration-200"
                                                style="height: {{ $v > 0 ? max(2, round($v / $hrMax * 100, 2)) : 0 }}%; min-height: {{ $v > 0 ? '6px' : '3px' }}"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mx-auto flex w-[min(100%,460px)] justify-around gap-6 px-4 pt-2">
                                @foreach (['Days worked', 'Late', 'Absences'] as $l)
                                    <span class="flex-1 text-center text-[0.8rem] font-bold text-[#737d8e]">{{ $l }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </article>
            </section>
        @endif

        {{-- ============ Accounting report ============ --}}
        @if ($acc)
            <section id="accountingReportPanel" role="tabpanel" aria-labelledby="accountingReportTab" x-show="tab === 'accounting'" @if ($tab !== 'accounting') x-cloak @endif>
                <div class="grid gap-[18px]">

                    <div class="grid gap-[18px] md:grid-cols-2">
                        <article class="grid gap-2 rounded-[22px] border border-[#ecebea] bg-white px-6 py-[22px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                            <span class="text-[0.82rem] font-bold uppercase tracking-[0.08em] text-gray-500">Total Salary Given</span>
                            <strong class="text-[clamp(1.8rem,2.3vw,2.5rem)] tracking-[-0.04em] text-gray-900">{{ $peso($acc['salary']) }}</strong>
                            <small class="text-[0.8rem] text-gray-500">{{ $acc['hasData'] ? $acc['period'] : 'No data' }}</small>
                        </article>
                        <article class="grid gap-2 rounded-[22px] border border-[#ecebea] bg-white px-6 py-[22px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                            <span class="text-[0.82rem] font-bold uppercase tracking-[0.08em] text-gray-500">Total Deducted</span>
                            <strong class="text-[clamp(1.8rem,2.3vw,2.5rem)] tracking-[-0.04em] text-[#b22222]">{{ $peso($acc['deductions']) }}</strong>
                            <small class="text-[0.8rem] text-gray-500">{{ $acc['hasData'] ? $acc['period'] : 'No data' }}</small>
                        </article>
                    </div>

                    <article class="rounded-[22px] border border-[#ecebea] bg-white px-6 pb-[18px] pt-[22px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                        <div class="mb-[18px] flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                            <div>
                                <p class="mb-2 text-[0.78rem] font-bold uppercase tracking-[0.08em] text-gray-500">Accounting</p>
                                <h2 class="text-[clamp(1.4rem,1.8vw,2rem)] font-bold text-gray-900">Monthly Salary Summary</h2>
                            </div>

                            <div class="flex w-full items-end gap-2.5 sm:w-auto" aria-label="Payroll report date filters">
                                <label class="grid min-w-0 flex-1 gap-[5px] text-[0.76rem] font-bold text-gray-500 sm:min-w-[140px] sm:flex-none">
                                    <span>Month</span>
                                    <select name="pay_month" aria-label="Filter payroll report by month" class="{{ $select }}" @change="$refs.form.submit()">
                                        @foreach (range(1, 12) as $m)
                                            <option value="{{ sprintf('%02d', $m) }}" @selected($acc['month'] === sprintf('%02d', $m))>{{ \Illuminate\Support\Carbon::create(2000, $m, 1)->format('F') }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="grid min-w-0 flex-1 gap-[5px] text-[0.76rem] font-bold text-gray-500 sm:min-w-[110px] sm:flex-none">
                                    <span>Year</span>
                                    <select name="pay_year" aria-label="Filter payroll report by year" class="{{ $select }}" @change="$refs.form.submit()">
                                        @foreach ($acc['years'] as $y)
                                            <option value="{{ $y }}" @selected($acc['year'] === $y)>{{ $y }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                        </div>

                        @if (! $acc['hasData'])
                            <div class="grid min-h-[200px] place-items-center rounded-2xl border border-dashed border-[#ecebea] bg-[#f9fafb] text-gray-500">
                                No payroll data is available for the selected month.
                            </div>
                        @else
                            <div class="grid min-h-[420px] items-center gap-[clamp(24px,5vw,72px)] px-[clamp(16px,5vw,64px)] py-6 md:grid-cols-[minmax(200px,0.35fr)_minmax(0,1fr)]"
                                aria-label="{{ $acc['period'] }}: salary {{ $peso($acc['salary']) }}, deductions {{ $peso($acc['deductions']) }}">
                                <div class="grid justify-items-center gap-5 text-center md:justify-items-start md:text-left">
                                    <div class="text-[clamp(1.2rem,2vw,1.65rem)] font-bold tracking-[0.04em] text-gray-900">{{ $acc['period'] }}</div>
                                    <div class="flex flex-col items-center gap-3 text-[clamp(0.95rem,1.5vw,1.15rem)] text-gray-500 md:items-start">
                                        <span class="font-bold text-[#1d7a4d]">Salary: {{ $peso($acc['salary']) }}</span>
                                        <span class="font-bold text-[#b22222]">Deductions: {{ $peso($acc['deductions']) }}</span>
                                    </div>
                                    <div class="grid gap-[5px] text-[0.7rem] text-gray-500" aria-hidden="true">
                                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#1d7a4d]"></span>Salary</span>
                                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#b22222]"></span>Deductions</span>
                                    </div>
                                </div>

                                <div class="min-w-0" role="img" aria-label="{{ $acc['year'] }} monthly salary and deductions">
                                    <div class="flex h-[280px] items-end gap-1 border-b border-[#e5e7eb] sm:gap-2">
                                        @foreach ($acc['series'] as $s)
                                            <div class="flex h-full flex-1 items-end justify-center gap-[3px] rounded-t-lg px-0.5 {{ $s['month'] === $acc['month'] ? 'bg-[#fef2f2]' : '' }}"
                                                title="{{ $s['label'] }} {{ $acc['year'] }} | Salary {{ $peso($s['salary']) }} | Deductions {{ $peso($s['deductions']) }}">
                                                <div class="w-1/2 max-w-[18px] rounded-t-md bg-gradient-to-t from-[#1d7a4d] to-[#2ec27d]"
                                                    style="height: {{ $s['salary'] > 0 ? max(2, round($s['salary'] / $acc['seriesMax'] * 100, 2)) : 0 }}%"></div>
                                                <div class="w-1/2 max-w-[18px] rounded-t-md bg-gradient-to-t from-[#b22222] to-[#ef5a5a]"
                                                    style="height: {{ $s['deductions'] > 0 ? max(2, round($s['deductions'] / $acc['seriesMax'] * 100, 2)) : 0 }}%"></div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="flex gap-1 pt-2 sm:gap-2">
                                        @foreach ($acc['series'] as $s)
                                            <span class="flex-1 text-center text-[0.7rem] font-bold sm:text-[0.76rem] {{ $s['month'] === $acc['month'] ? 'text-[#b22222]' : 'text-gray-500' }}">{{ $s['label'] }}</span>
                                        @endforeach
                                    </div>
                                    <p class="mt-3 text-center text-[0.76rem] text-gray-400">{{ $acc['year'] }} | highlighted month is the one shown on the left | hover a bar for the amounts</p>
                                </div>
                            </div>
                        @endif
                    </article>
                </div>
            </section>
        @endif

        {{-- If JS is off the filters still work with this button --}}
        <noscript><button type="submit" class="mt-4 rounded-xl bg-[#b22222] px-4 py-2 font-bold text-white">Apply filters</button></noscript>
    </form>

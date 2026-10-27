{{-- resources/views/employee/attendance.blade.php --}}

    <x-slot name="header">
        <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight ml-[80px] text-red-700">Attendance</h1>
    </x-slot>

    @php
        // Same pill colours as the HTML design (.status-pill)
        $pill = fn (string $status) => match ($status) {
            'Present'  => 'bg-[rgba(34,197,94,0.12)] text-[#15803d]',
            'Late'     => 'bg-[rgba(251,191,36,0.18)] text-[#a16207]',
            'Half Day' => 'bg-[rgba(96,165,250,0.18)] text-[#1d4ed8]',
            'Absent'   => 'bg-[rgba(239,68,68,0.1)] text-[#dc2626]',
            default    => 'bg-[#dbeafe] text-[#1d4ed8]',
        };

        $inputClass = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 text-[15px] text-gray-900 transition focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10';

        $tabBase   = 'inline-flex items-center justify-center gap-2 rounded-[10px] border px-3.5 py-2.5 font-semibold transition hover:-translate-y-px';
        $tabActive = 'border-[#b22222] bg-[#b22222] text-white hover:bg-[#951717] hover:shadow-[0_5px_12px_rgba(178,34,34,0.2)]';
        $tabIdle   = 'border-transparent bg-[#f0f0f2] text-[#3d3f46] hover:border-[#b22222]/20 hover:bg-[#fff5f5] hover:text-[#8f1b1b]';

        $archiveMonth = now()->subMonthNoOverflow()->format('Y-m');

        // Stat cards follow the old page: late days still count as days worked, half days count 0.5
        $daysWorked   = $summary['present'] + $summary['late'] + ($summary['half_day'] * 0.5);
        $attendedDays = $summary['present'] + $summary['late'] + $summary['half_day'];
        $avgHours     = $attendedDays > 0 ? $summary['hours'] / $attendedDays : 0;

        $stats = [
            ['Days Present',  rtrim(rtrim(number_format($daysWorked, 1), '0'), '.')],
            ['Days Late',     $summary['late']],
            ['Days Absent',   $summary['absent']],
            ['Half Days',     $summary['half_day']],
            ['Avg. Hours/Day', number_format($avgHours, 2)],
        ];

        // Table columns (6 columns now: added "Late (mins)")
        $columns = [
            'Date'         => 'w-[18%]',
            'Time In'      => 'w-[15%]',
            'Time Out'     => 'w-[15%]',
            'Late (mins)'  => 'w-[14%]',
            'Hours Worked' => 'w-[18%]',
            'Status'       => 'w-[20%]',
        ];

        $cell = 'border-b border-gray-900/[0.06] px-[18px] py-[22px] text-center align-middle text-gray-900';
    @endphp

    <div class="mx-auto max-w-[1920px] px-4 pb-12 pt-2 sm:px-6 lg:px-10" x-data>

        @if (! $employee)
            <div class="rounded-[20px] bg-white p-10 text-center text-gray-500 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                <i class="fa-solid fa-link-slash mb-3 text-4xl text-gray-300"></i>
                <p class="font-semibold">Your account is not linked to an employee record yet.</p>
                <p class="mt-1 text-sm">Please contact HR.</p>
            </div>
        @else
            <section aria-label="Employee Attendance Overview" class="grid gap-[22px]">

                {{-- Summary metrics --}}
                <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 min-[1100px]:grid-cols-5 min-[720px]:grid-cols-3" aria-label="Attendance summary metrics">
                    @foreach ($stats as [$label, $value])
                        <article class="flex min-h-[122px] flex-col justify-center rounded-2xl border border-gray-900/[0.03] bg-white/80 px-[18px] pb-3.5 pt-[18px] shadow-[0_2px_8px_rgba(15,23,42,0.03)]">
                            <div class="mb-2 text-[0.96rem] font-semibold leading-tight text-gray-700">{{ $label }}</div>
                            <div class="text-[clamp(2.3rem,2vw,3.2rem)] font-extrabold leading-none tracking-[-0.06em] text-gray-900">{{ $value }}</div>
                        </article>
                    @endforeach
                </div>

                {{-- Work schedule (auto-adjusts to 8:00 AM - 4:00 PM in May) --}}
                @if ($schedule)
                    <div class="flex flex-wrap items-center gap-x-8 gap-y-3 rounded-2xl border border-gray-900/[0.03] bg-white/80 px-[22px] py-4 shadow-[0_2px_8px_rgba(15,23,42,0.03)]" aria-label="Work schedule">
                        <div class="flex items-center gap-2 font-bold text-gray-900">
                            <i class="fa-regular fa-clock text-[#b22222]"></i> Work Schedule
                        </div>
                        <div class="text-sm text-gray-600">Work hours: <strong class="text-gray-900">{{ $schedule->label() }}</strong></div>
                        <div class="text-sm text-gray-600">Lunch: <strong class="text-gray-900">{{ $schedule->lunchLabel() }}</strong></div>
                        <div class="text-sm text-gray-600">Grace period: <strong class="text-gray-900">{{ $schedule->graceMinutes ? $schedule->graceMinutes.' mins' : 'None' }}</strong></div>
                        <div class="text-sm text-gray-600">Overtime: <strong class="text-gray-900">Not counted</strong></div>
                    </div>
                @endif

                {{-- Attendance panel --}}
                <div class="overflow-hidden rounded-[18px] border border-gray-900/5 bg-white/50 shadow-[0_4px_14px_rgba(15,23,42,0.02)]">

                    {{-- Title + export --}}
                    <div class="flex flex-col items-start justify-between gap-[18px] px-[22px] pb-3.5 pt-5 min-[720px]:flex-row min-[720px]:items-center">
                        <div>
                            <h2 class="text-[clamp(1.8rem,2vw,3rem)] font-bold tracking-[-0.05em] text-gray-900">
                                {{ $isCurrent ? 'Current Month Attendance' : 'Attendance Archive' }}
                            </h2>
                            <p class="mt-1 text-sm font-semibold text-gray-500">{{ $month->format('F Y') }}</p>
                        </div>

                        <button type="button" id="exportBtn" data-file="attendance-{{ $month->format('Y-m') }}.xlsx"
                            @disabled($records->isEmpty())
                            class="inline-flex items-center justify-center gap-2.5 whitespace-nowrap rounded-xl border border-gray-900/10 bg-[#f7f7f8] px-[18px] py-3 font-semibold text-gray-900 shadow-[0_2px_8px_rgba(15,23,42,0.03)] transition hover:-translate-y-px hover:border-[#b22222]/30 hover:bg-[#fff5f5] hover:shadow-[0_6px_16px_rgba(15,23,42,0.08)] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0">
                            <i class="fa-solid fa-download"></i> Export to Excel
                        </button>
                    </div>

                    {{-- Tabs --}}
                    <div class="flex flex-wrap items-center gap-2.5 px-[22px] pb-3.5" role="tablist" aria-label="Attendance periods">
                        <a href="{{ route('attendance.attendance-employee.index', array_filter(['status' => $status])) }}"
                            role="tab" aria-selected="{{ $isCurrent ? 'true' : 'false' }}"
                            class="{{ $tabBase }} {{ $isCurrent ? $tabActive : $tabIdle }}">
                            <i class="fa-regular fa-calendar-days"></i> Current Month
                        </a>
                        <a href="{{ route('attendance.attendance-employee.index', array_filter(['month' => $isCurrent ? $archiveMonth : $month->format('Y-m'), 'status' => $status])) }}"
                            role="tab" aria-selected="{{ $isCurrent ? 'false' : 'true' }}"
                            class="{{ $tabBase }} {{ $isCurrent ? $tabIdle : $tabActive }}">
                            <i class="fa-solid fa-archive"></i> Archive
                        </a>
                    </div>

                    {{-- Filters: month picker only in Archive, status always --}}
                    <form method="GET" action="{{ route('attendance.attendance-employee.index') }}" x-ref="filters"
                        class="flex flex-wrap items-end gap-3.5 px-[22px] pb-4">

                        @unless ($isCurrent)
                            <div class="flex items-end gap-2">
                                <a href="{{ route('attendance.attendance-employee.index', array_filter(['month' => $prevMonth, 'status' => $status])) }}"
                                    aria-label="Previous month"
                                    class="grid size-11 place-items-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                                <label for="monthFilter" class="flex min-w-[170px] flex-col gap-2 text-sm font-bold text-gray-600">
                                    <span>Month</span>
                                    <input type="month" id="monthFilter" name="month" value="{{ $month->format('Y-m') }}"
                                        max="{{ $archiveMonth }}" @change="$refs.filters.submit()" class="{{ $inputClass }}">
                                </label>
                                @if ($nextMonth < now()->format('Y-m'))
                                    <a href="{{ route('attendance.attendance-employee.index', array_filter(['month' => $nextMonth, 'status' => $status])) }}"
                                        aria-label="Next month"
                                        class="grid size-11 place-items-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                @endif
                            </div>
                        @endunless

                        <label for="statusFilter" class="flex min-w-[180px] flex-col gap-2 text-sm font-bold text-gray-600">
                            <span>Status</span>
                            <select id="statusFilter" name="status" @change="$refs.filters.submit()" class="{{ $inputClass }}">
                                <option value="">All Status</option>
                                @foreach (\App\Models\AttendanceRecord::STATUSES as $s)
                                    <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                        </label>

                        @if ($status)
                            <a href="{{ route('attendance.attendance-employee.index', array_filter(['month' => $isCurrent ? null : $month->format('Y-m')])) }}"
                                class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-600 transition hover:bg-gray-50">
                                Clear
                            </a>
                        @endif
                    </form>

                    {{-- Table --}}
                    <div class="overflow-x-auto border-t border-gray-900/[0.07] bg-white/60">
                        <table id="attendanceTable" class="w-full min-w-[860px] table-fixed border-collapse">
                            <thead>
                                <tr>
                                    @foreach ($columns as $heading => $width)
                                        <th class="{{ $width }} border-b border-gray-900/[0.08] px-[18px] pb-3.5 pt-[18px] text-center align-middle text-[1.1rem] font-bold text-gray-900">{{ $heading }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $record)
                                    @php
                                        $late  = (int) ($record->late_minutes ?? 0);
                                        // Hours computed from the schedule (no overtime, lunch deducted).
                                        // Falls back to the stored label when there is no time out yet.
                                        $hours = ($record->computed_hours ?? 0) > 0
                                            ? number_format($record->computed_hours, 2)
                                            : $record->hours_label;
                                    @endphp
                                    <tr class="transition hover:bg-[#fff7f7]">
                                        <td class="{{ $cell }}">{{ $record->date->format('Y-m-d') }}</td>
                                        <td class="{{ $cell }}">{{ $record->time_in_label }}</td>
                                        <td class="{{ $cell }}">{{ $record->time_out_label }}</td>
                                        <td class="{{ $cell }}">
                                            @if ($late > 0)
                                                <span class="font-bold text-[#a16207]">{{ $late }}</span>
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td class="{{ $cell }}">{{ $hours }}</td>
                                        <td class="border-b border-gray-900/[0.06] px-[18px] py-[22px] text-center align-middle">
                                            <span class="inline-flex min-w-[98px] items-center justify-center rounded-full px-3.5 py-2 text-[0.9rem] font-bold tracking-[-0.01em] {{ $pill($record->status) }}">
                                                {{ $record->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-empty>
                                        <td colspan="6" class="px-[18px] pb-[54px] pt-[42px] text-center text-[1.05rem] font-medium text-gray-500">
                                            No records found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($records->hasPages())
                        <div class="border-t border-gray-900/[0.07] px-[22px] py-4">{{ $records->links() }}</div>
                    @endif
                </div>
            </section>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        // Same idea as exportAttendanceToExcel() in the old page: exports the rows currently shown
        document.getElementById('exportBtn')?.addEventListener('click', (event) => {
            const table = document.getElementById('attendanceTable');
            if (!table || table.querySelector('tr[data-empty]')) return;

            const sheet = XLSX.utils.table_to_sheet(table);
            const book = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(book, sheet, 'Attendance');
            XLSX.writeFile(book, event.currentTarget.dataset.file || 'attendance.xlsx');
        });
    </script>
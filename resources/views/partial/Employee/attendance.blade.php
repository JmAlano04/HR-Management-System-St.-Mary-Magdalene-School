{{-- resources/views/employee/attendance.blade.php --}}

    {{-- Browser tab title + page heading --}}
    <x-slot name="title">
        Attendance
    </x-slot>

    @php
        $inputClass = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 text-[15px] text-gray-900 transition focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10';

        $badge = fn (string $status) => match ($status) {
            'Present'  => 'bg-green-100 text-green-800',
            'Late'     => 'bg-amber-100 text-amber-800',
            'Half Day' => 'bg-blue-100 text-blue-800',
            'Absent'   => 'bg-red-100 text-red-800',
            default    => 'bg-gray-100 text-gray-700',
        };
    @endphp

    <div class="px-4 pb-12 pt-2 sm:px-6 lg:px-10" x-data>

        @if (! $employee)
            <div class="rounded-[20px] bg-white p-10 text-center text-gray-500 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                <i class="fa-solid fa-link-slash mb-3 text-4xl text-gray-300"></i>
                <p class="font-semibold">Your account is not linked to an employee record yet.</p>
                <p class="mt-1 text-sm">Please contact HR.</p>
            </div>
        @else
            <section aria-label="Attendance" class="flex flex-col gap-5">

                {{-- Summary for the selected month --}}
                <div class="grid grid-cols-2 gap-[14px] md:grid-cols-3 xl:grid-cols-5">
                    @foreach ([
                        ['Present',     $summary['present'],  'fa-solid fa-circle-check', 'text-green-600'],
                        ['Late',        $summary['late'],     'fa-solid fa-clock',        'text-amber-600'],
                        ['Half Day',    $summary['half_day'], 'fa-solid fa-circle-half-stroke', 'text-blue-600'],
                        ['Absent',      $summary['absent'],   'fa-solid fa-circle-xmark', 'text-red-600'],
                        ['Total Hours', number_format($summary['hours'], 2), 'fa-solid fa-hourglass-half', 'text-[#b22222]'],
                    ] as [$label, $value, $icon, $color])
                        <div class="flex items-center gap-3.5 rounded-2xl bg-white p-[18px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                            <i class="{{ $icon }} {{ $color }} text-2xl"></i>
                            <div>
                                <p class="text-2xl font-extrabold leading-none text-gray-900">{{ $value }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-500">{{ $label }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Toolbar: month + status --}}
                <form method="GET" action="{{ route('attendance.attendance-employee.index') }}" x-ref="filters"
                    class="flex flex-wrap items-end gap-3.5 rounded-[20px] bg-white p-[18px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

                    <div class="flex items-center gap-2">
                        <a href="{{ route('attendance.attendance-employee.index', array_filter(['month' => $prevMonth, 'status' => $status])) }}"
                            aria-label="Previous month"
                            class="grid size-11 place-items-center rounded-xl border border-gray-200 text-gray-600 transition hover:bg-gray-50">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                        <label for="monthFilter" class="flex min-w-[170px] flex-col gap-2 text-sm font-bold text-gray-600">
                            <span>Month</span>
                            <input type="month" id="monthFilter" name="month" value="{{ $month->format('Y-m') }}"
                                max="{{ now()->format('Y-m') }}" @change="$refs.filters.submit()" class="{{ $inputClass }}">
                        </label>
                        @if (! $isCurrent)
                            <a href="{{ route('attendance.attendance-employee.index', array_filter(['month' => $nextMonth, 'status' => $status])) }}"
                                aria-label="Next month"
                                class="grid size-11 place-items-center rounded-xl border border-gray-200 text-gray-600 transition hover:bg-gray-50">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>

                    <label for="statusFilter" class="flex min-w-[180px] flex-col gap-2 text-sm font-bold text-gray-600">
                        <span>Status</span>
                        <select id="statusFilter" name="status" @change="$refs.filters.submit()" class="{{ $inputClass }}">
                            <option value="">All Status</option>
                            @foreach (\App\Models\AttendanceRecord::STATUSES as $s)
                                <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </label>

                    @if ($status || ! $isCurrent)
                        <a href="{{ route('attendance.attendance-employee.index') }}"
                            class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-600 transition hover:bg-gray-50">
                            Clear
                        </a>
                    @endif
                </form>

                {{-- Attendance table --}}
                @if ($records->isEmpty())
                    <div class="rounded-[20px] bg-white p-10 text-center text-gray-500 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                        <i class="fa-solid fa-calendar-xmark mb-3 text-4xl text-gray-300"></i>
                        <p class="font-semibold">No attendance records for {{ $month->format('F Y') }}.</p>
                    </div>
                @else
                    <div class="overflow-x-auto rounded-[20px] bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                        <table class="w-full min-w-[640px] text-left text-[15px]">
                            <thead>
                                <tr class="border-b border-gray-100 text-sm text-gray-500">
                                    <th class="px-5 py-4 font-bold">Date</th>
                                    <th class="px-5 py-4 font-bold">Day</th>
                                    <th class="px-5 py-4 font-bold">Time In</th>
                                    <th class="px-5 py-4 font-bold">Time Out</th>
                                    <th class="px-5 py-4 font-bold">Hours</th>
                                    <th class="px-5 py-4 font-bold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($records as $record)
                                    <tr class="transition hover:bg-[#fff7f7]">
                                        <td class="px-5 py-3.5 font-semibold text-gray-900">{{ $record->date->format('M d, Y') }}</td>
                                        <td class="px-5 py-3.5 text-gray-600">{{ $record->date->format('l') }}</td>
                                        <td class="px-5 py-3.5 text-gray-600">{{ $record->time_in_label }}</td>
                                        <td class="px-5 py-3.5 text-gray-600">{{ $record->time_out_label }}</td>
                                        <td class="px-5 py-3.5 text-gray-600">{{ $record->hours_label }}</td>
                                        <td class="px-5 py-3.5">
                                            <span class="inline-block rounded-full px-3 py-1 text-xs font-bold {{ $badge($record->status) }}">
                                                {{ $record->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div>{{ $records->links() }}</div>
                @endif
            </section>
        @endif
    </div>

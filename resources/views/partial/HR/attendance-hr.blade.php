{{-- resources/views/hr/attendance/index.blade.php --}}

    {{-- Browser tab title --}}
    <x-slot name="title">
      Attendance Management
    </x-slot>

    @php
        $inputClass = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 text-[15px] text-gray-900 transition focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10';
        $labelClass = 'flex min-w-0 flex-col gap-2 text-sm font-bold text-gray-600';
        $iconBtn    = 'grid h-11 w-11 cursor-pointer place-items-center rounded-[10px] border border-black/5 bg-white text-gray-900 transition hover:-translate-y-px hover:bg-red-50 hover:text-[#b22222]';
        $cardClass  = 'rounded-[20px] bg-white shadow-[0_10px_30px_rgba(15,23,42,0.05)]';

        $badge = [
            'Present'                => 'bg-emerald-50 text-emerald-800',
            'Absent'                 => 'bg-red-100 text-[#b22222]',
            'Late'                   => 'bg-orange-50 text-amber-800',
            'Half Day'               => 'bg-blue-50 text-blue-800',
            'Paid Leave'             => 'bg-violet-50 text-violet-800',
            'Unpaid Leave'           => 'bg-gray-100 text-gray-600',
            'Paid Holiday'           => 'bg-emerald-50 text-emerald-800',
            'Non-Working Day'        => 'bg-gray-100 text-gray-600',
            'Non-Working Day Review' => 'bg-yellow-100 text-yellow-800',
        ];

        $isArchive    = $view === 'archive';
        $inFolder     = $isArchive && $folderDate;
        $showRecords  = $records !== null;
        $pageIds      = $showRecords ? $records->getCollection()->pluck('id')->values() : collect();
        $hasFilters   = request()->hasAny(['search', 'date', 'department', 'status']);
        $title        = $isArchive ? 'Attendance Archive' : 'Current Month Attendance';
    @endphp

    <div
        class="px-4 pb-24 pt-2 sm:px-6 lg:px-10"
        x-data="attendancePage(@js([
            'employees'  => $employees,
            'exportRows' => $exportRows,
            'pageIds'    => $pageIds,
            'isArchive'  => $isArchive,
            'addOpen'    => $errors->attendance->any(),
            'nwdOpen'    => $errors->nwd->any(),
            'old'        => [
                'employee_id' => old('employee_id'),
                'status'      => old('status', 'Present'),
                'time_in'     => old('time_in'),
                'time_out'    => old('time_out'),
                'date'        => old('date', request('date') ?: now()->toDateString()),
                'pay_type'    => old('pay_type', 'unpaid'),
            ],
        ]))"
        @keydown.escape.window="addOpen = false; nwdOpen = false"
    >

        <section aria-label="Attendance management" class="flex flex-col gap-5">

            {{-- Flash message --}}
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition
                    class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any() && ! $errors->attendance->any() && ! $errors->nwd->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-[#b22222]">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Toolbar: search + filters (runs in the database) --}}
            <form method="GET" action="{{ route('attendance.index') }}" x-ref="filters"
                class="flex flex-wrap items-end gap-3.5 {{ $cardClass }} p-[18px]">

                <input type="hidden" name="view" value="{{ $view }}">
                @if ($folderDate)
                    <input type="hidden" name="folder" value="{{ $folderDate }}">
                @endif

                <label for="attendanceSearch" class="relative flex min-w-[240px] flex-[1_1_280px] flex-col gap-2 text-sm font-bold text-gray-600">
                    <i class="fa-solid fa-magnifying-glass absolute bottom-[14px] left-[14px] text-gray-400"></i>
                    <input type="search" id="attendanceSearch" name="search" value="{{ request('search') }}"
                        placeholder="Search by name or ID..."
                        @input.debounce.400ms="$refs.filters.submit()"
                        x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-3 pl-[42px] pr-3.5 text-gray-900 focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10">
                </label>

                @unless ($isArchive)
                    <label for="dateFilter" class="{{ $labelClass }} min-w-[170px]">
                        <span>Date</span>
                        <input type="date" id="dateFilter" name="date" value="{{ request('date') }}"
                            @change="$refs.filters.submit()" class="{{ $inputClass }}">
                    </label>
                @endunless

                <label for="departmentFilter" class="{{ $labelClass }} min-w-[180px]">
                    <span>Department</span>
                    <select id="departmentFilter" name="department" @change="$refs.filters.submit()" class="{{ $inputClass }}">
                        <option value="">All Departments</option>
                        @foreach (\App\Models\SchoolEmployee::DEPARTMENTS as $value => $label)
                            <option value="{{ $value }}" @selected(request('department') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label for="statusFilter" class="{{ $labelClass }} min-w-[180px]">
                    <span>Status</span>
                    <select id="statusFilter" name="status" @change="$refs.filters.submit()" class="{{ $inputClass }}">
                        <option value="">All Status</option>
                        @foreach (array_merge($statuses, [\App\Models\Attendance::NWD_REVIEW]) as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </label>

                @if ($hasFilters)
                    <a href="{{ route('attendance.index', array_filter(['view' => $view, 'folder' => $folderDate])) }}"
                        class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-600 transition hover:bg-gray-50">
                        Clear
                    </a>
                @endif
            </form>

            {{-- Attendance panel --}}
            <div class="{{ $cardClass }} p-5">

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 class="text-[1.3rem] font-bold text-slate-900">{{ $title }}</h2>

                    @if ($showRecords)
                        <div class="flex items-center gap-2">
                            <button type="button" @click="nwdOpen = true" class="{{ $iconBtn }}"
                                aria-label="Non-Working Days Management" title="Non-Working Days Management">
                                <i class="fa-solid fa-calendar-xmark"></i>
                            </button>

                            <button type="button" @click="exportExcel()" class="{{ $iconBtn }}"
                                aria-label="Export to Excel" title="Export to Excel">
                                <i class="fa-solid fa-download"></i>
                            </button>

                            {{-- Select / archive (current) or delete (archive) --}}
                            <button type="button" x-show="!selectMode" @click="startSelect()" class="{{ $iconBtn }}"
                                aria-label="{{ $isArchive ? 'Select attendance to delete' : 'Select attendance to archive' }}"
                                title="{{ $isArchive ? 'Select attendance to delete' : 'Select attendance to archive' }}">
                                <i class="fa-solid fa-square-check"></i>
                            </button>

                            <form x-show="selectMode" x-cloak method="POST" class="flex items-center gap-2"
                                action="{{ $isArchive ? route('attendance.destroyMany') : route('attendance.archive') }}"
                                @submit="if (isArchive && !confirm('Permanently delete ' + selected.length + ' record(s)? This cannot be undone.')) $event.preventDefault()">
                                @csrf
                                @if ($isArchive)
                                    @method('DELETE')
                                    <input type="hidden" name="folder" value="{{ $folderDate }}">
                                @endif
                                <template x-for="id in selected" :key="id">
                                    <input type="hidden" name="ids[]" :value="id">
                                </template>

                                <button type="button" @click="cancelSelect()"
                                    class="rounded-[10px] border border-gray-200 px-3.5 py-2.5 text-sm font-bold text-gray-600 transition hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="!selected.length"
                                    class="inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded-[10px] bg-[#b22222] px-4 text-sm font-bold text-white transition hover:bg-[#d93c3c] disabled:cursor-not-allowed disabled:opacity-50">
                                    <i class="fa-solid {{ $isArchive ? 'fa-trash' : 'fa-box-archive' }}"></i>
                                    <span>{{ $isArchive ? 'Delete' : 'Archive' }} (<span x-text="selected.length"></span>)</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                {{-- Tabs --}}
                <div class="my-3 flex items-center gap-2.5 pl-1.5">
                    <a href="{{ route('attendance.index') }}"
                        class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm font-bold transition
                               {{ ! $isArchive ? 'border-red-200 bg-red-100 text-[#b22222]' : 'border-transparent text-gray-500 hover:bg-red-50 hover:text-[#b22222]' }}">
                        <i class="fa-solid fa-calendar-days"></i> Current Month
                    </a>
                    <a href="{{ route('attendance.index', ['view' => 'archive']) }}"
                        class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm font-bold transition
                               {{ $isArchive ? 'border-red-200 bg-red-100 text-[#b22222]' : 'border-transparent text-gray-500 hover:bg-red-50 hover:text-[#b22222]' }}">
                        <i class="fa-solid fa-box-archive"></i> Archive
                    </a>
                </div>

                {{-- Archive: back button when inside a folder --}}
                @if ($inFolder)
                    <div class="mb-3 flex items-center gap-3">
                        <a href="{{ route('attendance.index', ['view' => 'archive']) }}" aria-label="Back to folders" title="Back to folders"
                            class="grid h-10 w-10 place-items-center rounded-[10px] border border-gray-200 bg-white text-slate-900 transition hover:bg-red-50 hover:text-[#b22222]">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                        <span class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($folderDate)->format('F j, Y') }}</span>
                    </div>
                @endif

                {{-- Archive folders --}}
                @if ($isArchive && ! $folderDate)
                    @if ($folders->isEmpty())
                        <div class="p-10 text-center text-gray-500">
                            <i class="fa-solid fa-folder-open mb-3 text-4xl text-gray-300"></i>
                            <p class="font-semibold">No archived records.</p>
                        </div>
                    @else
                        <div class="flex flex-col gap-2.5">
                            @foreach ($folders as $folder)
                                <a href="{{ route('attendance.index', ['view' => 'archive', 'folder' => $folder->date->format('Y-m-d')]) }}"
                                    class="flex items-center justify-between rounded-[10px] border border-gray-200 bg-white px-4 py-3 shadow-sm transition hover:bg-slate-50 hover:shadow">
                                    <span class="text-base font-semibold text-slate-900">{{ $folder->date->format('F j, Y') }}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-900">
                                        {{ $folder->total }} record{{ $folder->total > 1 ? 's' : '' }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endif

                {{-- Records table --}}
                @if ($showRecords)
                    @if ($records->isEmpty())
                        <div class="p-10 text-center text-gray-500">
                            <i class="fa-solid fa-calendar-xmark mb-3 text-4xl text-gray-300"></i>
                            <p class="font-semibold">No records found.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto rounded-xl border border-gray-100">
                            <table class="w-full min-w-[850px] table-fixed border-collapse text-[0.84rem]">
                                <thead class="bg-slate-50">
                                    <tr class="text-left font-bold text-gray-900">
                                        <th x-show="selectMode" x-cloak class="w-10 px-2 py-3 text-center">
                                            <input type="checkbox" aria-label="Select all rows"
                                                :checked="selected.length === pageIds.length && pageIds.length > 0"
                                                @change="selected = $event.target.checked ? [...pageIds] : []"
                                                class="h-4 w-4 cursor-pointer accent-[#b22222]">
                                        </th>
                                        <th class="w-[21%] px-1.5 py-3">Employee</th>
                                        <th class="w-[13%] px-1.5 py-3">Employee Type</th>
                                        <th class="w-[13%] px-1.5 py-3">Department</th>
                                        <th class="w-[11%] px-1.5 py-3 text-center">Date</th>
                                        <th class="w-[8%] px-1.5 py-3 text-center">Time In</th>
                                        <th class="w-[8%] px-1.5 py-3 text-center">Time Out</th>
                                        <th class="w-[10%] px-1.5 py-3 text-center">Hours Worked</th>
                                        <th class="w-[16%] px-1.5 py-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($records as $r)
                                        <tr class="border-t border-gray-100 font-semibold text-gray-900 transition hover:bg-red-50/40">
                                            <td x-show="selectMode" x-cloak class="px-2 py-3 text-center">
                                                <input type="checkbox" value="{{ $r['id'] }}" x-model.number="selected"
                                                    aria-label="Select attendance for {{ $r['name'] }} on {{ $r['date'] }}"
                                                    class="h-4 w-4 cursor-pointer accent-[#b22222]">
                                            </td>
                                            <td class="break-words px-1.5 py-3">
                                                <span class="block font-bold text-gray-900">{{ $r['name'] }}</span>
                                                <small class="mt-0.5 block text-[0.78rem] font-medium text-gray-500">{{ $r['employee_code'] }}</small>
                                            </td>
                                            <td class="px-1.5 py-3">{{ $r['role_label'] }}</td>
                                            <td class="px-1.5 py-3">{{ $r['department_label'] }}</td>
                                            <td class="whitespace-nowrap px-1.5 py-3 text-center">{{ \Carbon\Carbon::parse($r['date'])->format('m/d/Y') }}</td>
                                            <td class="whitespace-nowrap px-1.5 py-3 text-center">{{ $r['time_in'] }}</td>
                                            <td class="whitespace-nowrap px-1.5 py-3 text-center">{{ $r['time_out'] }}</td>
                                            <td class="whitespace-nowrap px-1.5 py-3 text-center">{{ $r['hours'] }}</td>
                                            <td class="px-1.5 py-3 text-center">
                                                <span class="inline-block whitespace-nowrap rounded-full px-2.5 py-1.5 text-[0.84rem] font-bold {{ $badge[$r['status']] ?? 'bg-gray-100 text-gray-600' }}">
                                                    {{ $r['status'] }}
                                                </span>
                                                @if ($r['review_note'])
                                                    <small class="mt-1 block rounded-full bg-yellow-100 px-2 py-1 text-[0.72rem] font-bold text-yellow-800">
                                                        {{ $r['review_note'] }}
                                                    </small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $records->links() }}</div>
                    @endif
                @endif
            </div>
        </section>

        {{-- Floating Add Attendance button (current month only) --}}
        @unless ($isArchive)
            <button type="button" @click="openAdd()"
                class="fixed bottom-6 right-6 z-40 inline-flex cursor-pointer items-center gap-2 rounded-[10px] border border-[#8f241f] bg-[#8f241f] px-4 py-2.5 font-bold text-white shadow-[0_10px_24px_rgba(143,36,31,0.25)] transition hover:-translate-y-px hover:bg-[#741c19]">
                <i class="fa-solid fa-plus"></i> Add Attendance
            </button>
        @endunless

        {{-- ============ Add attendance modal ============ --}}
        <div x-show="addOpen" x-cloak x-transition.opacity
            @click.self="addOpen = false"
            class="fixed inset-0 z-[60] grid place-items-center bg-[rgba(3,7,18,0.5)] p-4">
            <form method="POST" action="{{ route('attendance.store') }}" role="dialog" aria-modal="true"
                class="relative max-h-[90vh] w-full max-w-[560px] overflow-y-auto rounded-[14px] bg-white p-6 shadow-[0_20px_50px_rgba(15,23,42,0.14)]">
                @csrf

                <button type="button" @click="addOpen = false" aria-label="Close"
                    class="absolute right-3 top-2 cursor-pointer text-3xl leading-none text-gray-500 hover:text-gray-800">&times;</button>

                <span class="text-[0.72rem] font-extrabold uppercase tracking-[0.12em] text-[#b22222]">Attendance record</span>
                <h3 class="mb-1 mt-1.5 text-2xl font-bold tracking-tight text-gray-900">Add attendance</h3>
                <p class="mb-4 text-sm text-gray-500">Enter one employee's attendance for a specific date.</p>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="{{ $labelClass }}">
                        Employee ID
                        <select name="employee_id" x-model="form.employee_id" required class="{{ $inputClass }}">
                            <option value="" disabled>Select an employee ID</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee['id'] }}">{{ $employee['code'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="{{ $labelClass }}">
                        Employee name
                        <input type="text" readonly :value="emp ? emp.name : ''" class="{{ $inputClass }}">
                    </label>
                    <label class="{{ $labelClass }}">
                        Employee type
                        <input type="text" readonly :value="emp ? emp.role : ''" class="{{ $inputClass }}">
                    </label>
                    <label class="{{ $labelClass }}">
                        Department
                        <input type="text" readonly :value="emp ? emp.department : ''" class="{{ $inputClass }}">
                    </label>
                    <label class="{{ $labelClass }}">
                        <span>Time in <small class="font-medium text-gray-400">Optional for absent</small></span>
                        <input type="time" name="time_in" x-model="form.time_in" class="{{ $inputClass }}">
                    </label>
                    <label class="{{ $labelClass }}">
                        <span>Time out <small class="font-medium text-gray-400">Optional for absent</small></span>
                        <input type="time" name="time_out" x-model="form.time_out" class="{{ $inputClass }}">
                    </label>
                    <label class="{{ $labelClass }}">
                        Attendance date
                        <input type="date" name="date" x-model="form.date" required class="{{ $inputClass }}">
                    </label>
                    <label class="{{ $labelClass }}">
                        Status
                        <select name="status" x-model="form.status" required class="{{ $inputClass }}">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                @if ($errors->attendance->any())
                    <p role="alert" class="mt-4 text-sm font-semibold text-[#b22222]">{{ $errors->attendance->first() }}</p>
                @endif

                <div class="mt-4 flex justify-end">
                    <button type="submit"
                        class="cursor-pointer rounded-[10px] border border-[#8f241f] bg-[#8f241f] px-4 py-2.5 font-bold text-white transition hover:bg-[#741c19]">
                        Save attendance
                    </button>
                </div>
            </form>
        </div>

        {{-- ============ Non-working days modal ============ --}}
        <div x-show="nwdOpen" x-cloak x-transition.opacity
            @click.self="nwdOpen = false"
            class="fixed inset-0 z-[60] grid place-items-center bg-[rgba(3,7,18,0.5)] p-4">
            <div role="dialog" aria-modal="true"
                class="relative max-h-[90vh] w-full max-w-[760px] overflow-y-auto rounded-[14px] bg-white p-6 shadow-[0_20px_50px_rgba(15,23,42,0.14)] sm:p-7">

                <button type="button" @click="nwdOpen = false" aria-label="Close"
                    class="absolute right-3 top-2 cursor-pointer text-3xl leading-none text-gray-500 hover:text-gray-800">&times;</button>

                <span class="text-[0.72rem] font-extrabold uppercase tracking-[0.12em] text-[#b22222]">HR administration</span>
                <h3 class="mb-1 mt-1.5 text-2xl font-bold tracking-tight text-gray-900">Non-Working Days Management</h3>
                <p class="mb-5 text-sm text-gray-500">Configure holidays and school suspensions. Attendance logs on these dates are flagged for review.</p>

                <form method="POST" action="{{ route('hr.non-working-days.store') }}" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                    @csrf
                    <label class="{{ $labelClass }}">
                        Date
                        <input type="date" name="date" value="{{ old('date') }}" required class="{{ $inputClass }}">
                    </label>
                    <label class="{{ $labelClass }}">
                        Description
                        <input type="text" name="name" value="{{ old('name') }}" maxlength="100" required
                            placeholder="e.g., National holiday" class="{{ $inputClass }}">
                    </label>
                    <label class="{{ $labelClass }}">
                        Classification
                        <select name="category" required class="{{ $inputClass }}">
                            @foreach (\App\Models\NonWorkingDay::CATEGORIES as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="{{ $labelClass }}">
                        Pay treatment
                        <select name="pay_type" x-model="form.pay_type" required class="{{ $inputClass }}">
                            <option value="unpaid">Unpaid / Regular non-working day</option>
                            <option value="paid">Paid holiday / declared paid break</option>
                        </select>
                    </label>
                    <label class="{{ $labelClass }}" x-show="form.pay_type === 'paid'" x-cloak>
                        Credited days
                        <input type="number" name="paid_days" min="0" max="1" step="0.5" value="1"
                            :disabled="form.pay_type !== 'paid'" class="{{ $inputClass }}">
                    </label>
                    <label class="{{ $labelClass }}" x-show="form.pay_type === 'paid'" x-cloak>
                        Credited hours
                        <input type="number" name="paid_hours" min="0" max="8" step="0.25" value="8"
                            :disabled="form.pay_type !== 'paid'" class="{{ $inputClass }}">
                    </label>

                    @if ($errors->nwd->any())
                        <p role="alert" class="text-sm font-semibold text-[#b22222] sm:col-span-2">{{ $errors->nwd->first() }}</p>
                    @endif

                    <div class="flex justify-end sm:col-span-2">
                        <button type="submit"
                            class="cursor-pointer rounded-[10px] border border-[#8f241f] bg-[#8f241f] px-4 py-2.5 font-bold text-white transition hover:bg-[#741c19]">
                            Save non-working day
                        </button>
                    </div>
                </form>

                <div class="mt-5 grid gap-2.5" aria-live="polite">
                    @forelse ($nonWorkingDays as $day)
                        <div class="flex items-center justify-between gap-4 rounded-[10px] border border-[#ecebea] bg-gray-50 px-3.5 py-3">
                            <div class="grid gap-0.5">
                                <strong>{{ $day->date->format('Y-m-d') }} - {{ $day->name }}</strong>
                                <small class="text-gray-500">
                                    {{ $day->category }} |
                                    @if ($day->isPaid())
                                        Paid: {{ (float) $day->paid_days }} day(s), {{ (float) $day->paid_hours }} hour(s)
                                    @else
                                        Unpaid: 0 credited days and hours
                                    @endif
                                </small>
                            </div>
                            <form method="POST" action="{{ route('non-working-days.destroy', $day) }}"
                                @submit="if (!confirm('Remove this non-working day?')) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="Remove non-working day {{ $day->name }} on {{ $day->date->format('Y-m-d') }}"
                                    class="cursor-pointer rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-sm font-bold text-red-800 transition hover:bg-red-50">
                                    Remove
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No non-working days configured.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script>
        function attendancePage(cfg) {
            return {
                employees: cfg.employees,
                exportRows: cfg.exportRows,
                pageIds: cfg.pageIds,
                isArchive: cfg.isArchive,

                addOpen: cfg.addOpen,
                nwdOpen: cfg.nwdOpen,

                selectMode: false,
                selected: [],

                form: { ...cfg.old },

                get emp() {
                    return this.form.employee_id ? this.employees[this.form.employee_id] || null : null;
                },

                openAdd() { this.addOpen = true; },

                startSelect() { this.selectMode = true; this.selected = []; },
                cancelSelect() { this.selectMode = false; this.selected = []; },

                exportExcel() {
                    if (!this.exportRows.length) { alert('No attendance rows to export'); return; }
                    const ws = XLSX.utils.json_to_sheet(this.exportRows);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, 'Attendance');
                    XLSX.writeFile(wb, 'attendance.xlsx');
                },
            };
        }
    </script>
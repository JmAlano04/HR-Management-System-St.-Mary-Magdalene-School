{{-- resources/views/admin/attendance-monitoring/index.blade.php --}}
 @auth
    @if (auth()->user()->hasRole('Admin'))
        <x-slot name="title">
            Admin Attendance Management
        </x-slot>
    @elseif (auth()->user()->hasRole('HR'))
        <x-slot name="title">
             Attendance Management 
        </x-slot>

        <x-slot name="header">
            <h1 class="text-2xl font-extrabold leading-none  ml-20 tracking-tight text-[#b22222] sm:text-4xl">Attendance Management</h1>
        </x-slot>
   @endif
@endauth

    @php

        // Look of the status badges
        $badge = fn (string $status) => match (strtolower($status)) {
            'present'  => 'bg-[#ecfdf5] text-[#065f46]',
            'absent'   => 'bg-[#fee2e2] text-[#b22222]',
            'late'     => 'bg-[#fff7ed] text-[#92400e]',
            'half day' => 'bg-[#eff6ff] text-[#1e40af]',
            default    => 'bg-gray-100 text-gray-600',
        };

        $isArchive  = $view === 'archive';
        $panelTitle = $isArchive ? 'Attendance Archive' : 'Current Month Attendance';
        $showActions = ! $showFolders;                       // hidden on the folder list
        $pageIds = $records ? $records->pluck('id')->map(fn ($id) => (string) $id)->all() : [];

        $inputClass = 'rounded-xl border border-[#e6e7ea] bg-white px-3 py-2.5 text-sm text-gray-800 outline-none focus:border-[#b22222] focus:ring-2 focus:ring-[#b22222]/15';
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="attendanceTable(@js(['pageIds' => $pageIds]))"
    >
        {{-- ============ Filters ============ --}}
        <form method="GET" action="{{ route('attendance.index') }}" class="mt-2 flex max-w-[1100px] flex-wrap items-center gap-3">
            <input type="hidden" name="view" value="{{ $view }}">
            @if ($folder)
                <input type="hidden" name="folder" value="{{ $folder }}">
            @endif

            <label class="relative min-w-[220px] flex-1">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by name or ID..."
                    aria-label="Search attendance"
                    class="{{ $inputClass }} w-full py-3 pl-10 pr-3.5">
            </label>

            @unless ($folder)
                <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" aria-label="Filter by date"
                    onchange="this.form.submit()" class="{{ $inputClass }}">
            @endunless

            <select name="department" aria-label="Filter by department" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="all">All Departments</option>
                @foreach ($departments as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['department'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" aria-label="Filter by status" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="all">All Status</option>
                @foreach ($statuses as $status)
                    <option value="{{ strtolower($status) }}" @selected(strtolower($filters['status'] ?? '') === strtolower($status))>{{ $status }}</option>
                @endforeach
            </select>

            <button type="submit" class="sr-only">Apply filters</button>
        </form>

        {{-- ============ Attendance panel ============ --}}
        <section aria-labelledby="attendancePanelTitle"
            class="mt-4 max-w-[1100px] rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-6">

            {{-- Title + actions --}}
            <div class="mb-3 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
                <h2 id="attendancePanelTitle" class="text-[1.4rem] font-bold text-slate-900">{{ $panelTitle }}</h2>

                @if ($showActions)
                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Select to Archive (current) / Select to Delete (inside an archive folder) --}}
                        <template x-if="!selectMode">
                            <button type="button" @click="selectMode = true"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] border border-black/5 bg-white px-3.5 py-2.5 font-bold text-gray-900 transition hover:border-[#b22222]/25 hover:bg-[#fff1f2] hover:text-[#b22222]">
                                <i class="fa-solid fa-square-check" aria-hidden="true"></i>
                                {{ $isArchive ? 'Select to Delete' : 'Select to Archive' }}
                            </button>
                        </template>

                        <template x-if="selectMode">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="cancelSelect()"
                                    class="cursor-pointer rounded-[10px] border border-gray-300 bg-white px-3.5 py-2.5 font-bold text-gray-600 transition hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit" form="bulkForm" :disabled="!selected.length"
                                    class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] bg-[#b22222] px-3.5 py-2.5 font-bold text-white transition hover:bg-[#941b1b] disabled:cursor-not-allowed disabled:opacity-50">
                                    <i class="fa-solid {{ $isArchive ? 'fa-trash' : 'fa-box-archive' }}" aria-hidden="true"></i>
                                    {{ $isArchive ? 'Delete' : 'Archive' }} selected (<span x-text="selected.length"></span>)
                                </button>
                            </div>
                        </template>

                        <a href="{{ route('attendance.export', request()->query()) }}"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] border border-black/5 bg-white px-3.5 py-2.5 font-bold text-gray-900 transition hover:border-[#b22222]/25 hover:bg-[#fff1f2] hover:text-[#b22222]">
                            <i class="fa-solid fa-download" aria-hidden="true"></i> Export to Excel
                        </a>
                    </div>
                @endif
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-3 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-3 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Tabs: Current Month / Archive --}}
            <div class="my-3 flex gap-2.5" role="tablist" aria-label="Attendance views">
                <a href="{{ route('attendance.index', ['view' => 'current']) }}" role="tab" aria-selected="{{ ! $isArchive ? 'true' : 'false' }}"
                    class="inline-flex items-center gap-2 rounded-full border px-3 py-2 font-bold transition {{ ! $isArchive ? 'border-[#f5c2c2] bg-[#fee2e2] text-[#7f1d1d]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Current Month
                </a>
                <a href="{{ route('attendance.index', ['view' => 'archive']) }}" role="tab" aria-selected="{{ $isArchive ? 'true' : 'false' }}"
                    class="inline-flex items-center gap-2 rounded-full border px-3 py-2 font-bold transition {{ $isArchive ? 'border-[#f5c2c2] bg-[#fee2e2] text-[#7f1d1d]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Archive
                </a>
            </div>

            {{-- Back button (inside an archive folder) --}}
            @if ($isArchive && $folder)
                <div class="mb-2 flex items-center gap-3">
                    <a href="{{ route('attendance.index', ['view' => 'archive']) }}" aria-label="Back to folders" title="Back to folders"
                        class="grid h-10 w-10 place-items-center rounded-[10px] border border-gray-300 bg-white transition hover:border-[#b22222]/25 hover:bg-[#fff1f2] hover:text-[#b22222]">
                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    </a>
                    <span class="font-bold text-slate-900">{{ \Illuminate\Support\Carbon::parse($folder)->format('F j, Y') }}</span>
                </div>
            @endif

            {{-- Archive folders --}}
            @if ($showFolders)
                <div class="grid gap-2.5 rounded-xl border border-[#f3f4f6] p-2">
                    @forelse ($folders as $f)
                        <a href="{{ route('attendance.index', ['view' => 'archive', 'folder' => \Illuminate\Support\Carbon::parse($f->date)->format('Y-m-d')]) }}"
                            class="flex w-full items-center justify-between rounded-[10px] border border-[#ececec] bg-white px-4 py-3 transition hover:border-[#d9a5a5] hover:bg-gray-50">
                            <span class="text-base font-semibold text-slate-900"><i class="fa-solid fa-folder mr-2 text-[#b22222]" aria-hidden="true"></i>{{ \Illuminate\Support\Carbon::parse($f->date)->format('F j, Y') }}</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1.5 font-semibold text-slate-900">{{ $f->total }} record{{ $f->total > 1 ? 's' : '' }}</span>
                        </a>
                    @empty
                        <div class="px-4 py-[26px] text-center text-gray-400">No records found</div>
                    @endforelse
                </div>
            @else
                {{-- Bulk form (archive / delete) — checkboxes below point here via form="bulkForm" --}}
                <form id="bulkForm" method="POST"
                    action="{{ $isArchive ? route('attendance.destroy') : route('attendance.archive') }}"
                    onsubmit="return confirm('{{ $isArchive ? 'Permanently delete the selected archived records?' : 'Move the selected records to the Archive?' }}');">
                    @csrf
                    @method($isArchive ? 'DELETE' : 'PATCH')
                </form>

                {{-- Attendance table --}}
                <div class="overflow-x-auto rounded-xl border border-[#f3f4f6] bg-white">
                    <table class="w-full min-w-[820px] border-collapse">
                        <thead class="bg-slate-50">
                            <tr>
                                <th x-show="selectMode" x-cloak class="w-px border-b border-[#f3f4f6] px-3 py-5 text-center">
                                    <input type="checkbox" aria-label="Select all rows" @change="toggleAll($event.target.checked)"
                                        :checked="selected.length && selected.length === pageIds.length"
                                        class="h-[18px] w-[18px] cursor-pointer accent-[#b22222]">
                                </th>
                                @foreach (['Employee ID', 'Name', 'Department', 'Date', 'Time In', 'Time Out', 'Hours Worked', 'Status'] as $heading)
                                    <th class="whitespace-nowrap border-b border-[#f3f4f6] px-2 py-5 text-left font-bold text-gray-900">{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($records as $record)
                                <tr class="transition-colors hover:bg-gray-50">
                                    <td x-show="selectMode" x-cloak class="border-b border-[#f3f4f6] px-3 py-5 text-center">
                                        <input type="checkbox" name="ids[]" form="bulkForm" value="{{ $record->id }}" x-model="selected"
                                            aria-label="Select row {{ $record->employee?->employee_id }}"
                                            class="h-[18px] w-[18px] cursor-pointer accent-[#b22222]">
                                    </td>
                                    <td class="whitespace-nowrap border-b border-[#f3f4f6] px-2 py-5 font-semibold">{{ $record->employee?->employee_id ?? 'N/A' }}</td>
                                    <td class="border-b border-[#f3f4f6] px-2 py-5 font-semibold">{{ $record->employee?->name ?? 'Unknown employee' }}</td>
                                    <td class="border-b border-[#f3f4f6] px-2 py-5 font-semibold">{{ \App\Models\AttendanceRecord::departmentLabel($record->employee?->department) }}</td>
                                    <td class="whitespace-nowrap border-b border-[#f3f4f6] px-2 py-5 font-semibold">{{ $record->date->format('M d, Y') }}</td>
                                    <td class="whitespace-nowrap border-b border-[#f3f4f6] px-2 py-5 font-semibold">{{ $record->time_in_label }}</td>
                                    <td class="whitespace-nowrap border-b border-[#f3f4f6] px-2 py-5 font-semibold">{{ $record->time_out_label }}</td>
                                    <td class="border-b border-[#f3f4f6] px-2 py-5 font-semibold">{{ $record->hours_label }}</td>
                                    <td class="border-b border-[#f3f4f6] px-2 py-5">
                                        <span class="inline-block whitespace-nowrap rounded-full px-2.5 py-1.5 text-[0.9rem] font-bold {{ $badge($record->status) }}">{{ $record->status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-[26px] text-center text-gray-400">No records found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($records->hasPages())
                    <div class="mt-5">{{ $records->links() }}</div>
                @endif
            @endif
        </section>
    </div>

    <script>
        function attendanceTable(cfg) {
            return {
                pageIds: cfg.pageIds,   // ids of the rows on this page (strings)
                selectMode: false,
                selected: [],

                toggleAll(checked) {
                    this.selected = checked ? [...this.pageIds] : [];
                },

                cancelSelect() {
                    this.selectMode = false;
                    this.selected = [];
                },
            };
        }
    </script>


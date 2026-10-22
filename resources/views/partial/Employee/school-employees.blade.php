{{-- resources/views/employee/school-employees.blade.php --}}

    {{-- Browser tab title --}}
    <x-slot name="title">
      School Employees
    </x-slot>

    @php
        $inputClass = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 text-[15px] text-gray-900 transition focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10';
    @endphp

    <div
        class="px-4 pb-12 pt-2 sm:px-6 lg:px-10"
        x-data="employeeDirectory(@js(['employees' => $cards]))"
        @keydown.escape.window="detailId = null"
    >

        <section aria-label="Employee directory" class="flex flex-col gap-5">

            {{-- Toolbar: search + filters (runs in the database) --}}
            <form method="GET" action="{{ route('employee.school-employees') }}" x-ref="filters"
                class="flex flex-wrap items-end gap-3.5 rounded-[20px] bg-white p-[18px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

                <label for="employeeSearch" class="relative flex min-w-[240px] flex-[1_1_280px] flex-col gap-2 text-sm font-bold text-gray-600">
                    <i class="fa-solid fa-magnifying-glass absolute bottom-[14px] left-[14px] text-gray-400"></i>
                    <input type="search" id="employeeSearch" name="search" value="{{ request('search') }}"
                        placeholder="Search employee name, ID or position"
                        @input.debounce.400ms="$refs.filters.submit()"
                        x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-3 pl-[42px] pr-3.5 text-gray-900 focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10">
                </label>

                <label for="departmentFilter" class="flex min-w-[180px] flex-col gap-2 text-sm font-bold text-gray-600">
                    <span>Department</span>
                    <select id="departmentFilter" name="department" @change="$refs.filters.submit()" class="{{ $inputClass }}">
                        <option value="">All Schools</option>
                        @foreach (\App\Models\SchoolEmployee::DEPARTMENTS as $value => $label)
                            <option value="{{ $value }}" @selected(request('department') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label for="roleFilter" class="flex min-w-[180px] flex-col gap-2 text-sm font-bold text-gray-600">
                    <span>Role Type</span>
                    <select id="roleFilter" name="role_type" @change="$refs.filters.submit()" class="{{ $inputClass }}">
                        <option value="">All Roles</option>
                        @foreach (\App\Models\SchoolEmployee::ROLE_TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(request('role_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                @if (request()->hasAny(['search', 'department', 'role_type']))
                    <a href="{{ route('employee.school-employees') }}"
                        class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold text-gray-600 transition hover:bg-gray-50">
                        Clear
                    </a>
                @endif
            </form>

            {{-- Employee cards --}}
            @if ($employees->isEmpty())
                <div class="rounded-[20px] bg-white p-10 text-center text-gray-500 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                    <i class="fa-solid fa-users-slash mb-3 text-4xl text-gray-300"></i>
                    <p class="font-semibold">No employees found.</p>
                </div>
            @else
                <div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-[18px]">
                    @foreach ($employees as $employee)
                        <article
                            @click="openDetail({{ $employee->id }})"
                            class="flex cursor-pointer flex-col items-center gap-3 rounded-2xl border border-[#f3e0e0] bg-gradient-to-b from-white to-[#fff7f7] px-[18px] py-[22px] text-center shadow-[0_8px_24px_rgba(15,23,42,0.06)] transition duration-200 hover:-translate-y-1.5 hover:shadow-[0_18px_36px_rgba(15,23,42,0.1)]"
                        >
                            <div class="grid h-[88px] w-[88px] shrink-0 place-items-center rounded-full bg-gradient-to-br from-[#b22222] to-[#dc2626] text-xl font-extrabold text-white">
                                {{ $employee->initials }}
                            </div>
                            <div class="flex flex-col items-center gap-1.5">
                                <h2 class="text-[1.05rem] font-bold text-gray-900">{{ $employee->name }}</h2>
                                <p class="text-[15px] text-gray-500">{{ $employee->employee_id }}</p>
                                <p class="text-[15px] text-gray-500">{{ $employee->position }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div>{{ $employees->links() }}</div>
            @endif
        </section>

        {{-- ============ Details modal (view only) ============ --}}
        <div x-show="detail" x-cloak x-transition.opacity
            @click.self="detailId = null"
            class="fixed inset-0 z-[60] grid place-items-center bg-[rgba(3,7,18,0.5)] p-4">
            <div role="dialog" aria-modal="true" class="relative max-h-[90vh] w-full max-w-[500px] overflow-y-auto rounded-[14px] bg-white px-7 pb-7 pt-9 text-center shadow-[0_20px_50px_rgba(15,23,42,0.14)]">
                <button type="button" @click="detailId = null" aria-label="Close"
                    class="absolute right-3 top-2 cursor-pointer text-3xl leading-none text-gray-500 hover:text-gray-800">&times;</button>

                <template x-if="detail">
                    <div>
                        <div class="mx-auto grid h-[88px] w-[88px] place-items-center rounded-full bg-gradient-to-br from-[#b22222] to-[#dc2626] text-xl font-extrabold text-white" x-text="detail.initials"></div>

                        <h3 class="mt-4 text-lg font-bold" x-text="detail.name"></h3>

                        <div class="mt-3 space-y-1.5 text-gray-700">
                            <p><strong>ID:</strong> <span x-text="detail.employee_id"></span></p>
                            <p><strong>Position:</strong> <span x-text="detail.position"></span></p>
                            <p><strong>Department:</strong> <span x-text="detail.department_label"></span></p>
                            <p><strong>Role Type:</strong> <span x-text="detail.role_label"></span></p>
                            <p><strong>Email:</strong> <span x-text="detail.email"></span></p>
                            <p><strong>Birthday:</strong> <span x-text="detail.birthday_label"></span></p>
                            <p><strong>Emergency Contact:</strong> <span x-text=" detail.emergency_contact"></span></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function employeeDirectory(cfg) {
            return {
                employees: cfg.employees,
                detailId: null,

                get detail() {
                    return this.detailId ? this.employees[this.detailId] : null;
                },

                openDetail(id) { this.detailId = id; },
            };
        }
    </script>

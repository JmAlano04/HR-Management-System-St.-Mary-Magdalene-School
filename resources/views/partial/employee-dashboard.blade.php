{{-- resources/views/employees/index.blade.php --}}

    {{-- Browser tab title --}}
   
   @auth
    <x-slot name="title">
        @if (auth()->user()->hasRole('Admin'))
            Admin Employee Monitoring
        @elseif (auth()->user()->hasRole('HR'))
            HR Employee Management
        @elseif (auth()->user()->hasRole('Accounting'))
            Accounting Employee Management
        @elseif (auth()->user()->hasRole('Employee'))
            Employee Employee Management
        @elseif (auth()->user()->hasRole('Applicant'))
            Applicant Dashboard
        @else
            Dashboard
        @endif
    </x-slot>

  
@endauth


{{-- Page heading (shown in the layout's top bar) --}}
    <x-slot name="header">
        <h1 class="ml-20 text-2xl font-extrabold leading-none tracking-tight text-[#b22222] sm:text-4xl">Employee Management</h1>
    </x-slot>
    @php
        $inputClass = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 text-[15px] text-gray-900 transition focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10';
        $labelClass = 'mb-1.5 block text-sm font-bold text-gray-600';
        $errClass   = 'mt-1 text-xs font-semibold text-red-600';

        // Only HR can add / edit / delete (Admin can view only)
        $canManage = auth()->user()->hasRole('HR');

        // Keep what the user typed if validation failed, so the form can reopen
        $old = collect(old())->only([
            'employee_source', 'user_id', 'employee_id', 'first_name', 'middle_name', 'last_name', 'suffix',
            'position', 'department', 'role_type', 'employment_status', 'step_no', 'email', 'gender',
            'birthday', 'address_line', 'address_landmark', 'barangay', 'city_municipality', 'province', 'zip_code',
            'emergency_name', 'emergency_contact',
        ])->all();

        $roleTypes = [
            'Administrative Staff' => 'Administrative Staff',
            'Teaching Staff'       => 'Teaching Staff',
            'Maintenance Staff'    => 'Maintenance Staff',
            'Support Staff'        => 'Support Staff',
        ];
        $filterRoleTypes = collect($filterRoleTypes ?? $roleTypes);

        // Employment status: stored value => label.
        // Uses the controller's list if it passed one.
        $employmentStatuses = $employmentStatuses ?? [
            'regular'     => 'Regular',
            'non regular' => 'Probationary',
        ];

        // Payroll steps from the payroll_steps table: step_no => label
        $payrollSteps = $payrollSteps ?? collect();

        // Default step for the Add form (first step in the table)
        $defaultStepNo = (string) ($defaultStepNo ?? $payrollSteps->keys()->first() ?? '');

        $filterDepartments = collect($filterDepartments ?? []);
        $formDepartments   = collect($formDepartments ?? []);
    @endphp

    <div
        class="px-4 pb-12 pt-2 sm:px-6 lg:px-10"
        x-data="employeeDirectory(@js([
            'employees'   => $cards,
            'applicants'  => $applicants,
            'statuses'    => $employmentStatuses,
            'defaultStep' => $defaultStepNo,
            'old'         => $old,
            'reopen'      => $errors->any(),
            'editingId'   => old('_editing_id'),
            'storeUrl'    => route('employees.store'),
            'updateUrl'   => route('employees.update', '__ID__'),
            'destroyUrl'  => route('employees.destroy', '__ID__'),
        ]))"
        @keydown.escape.window="closeAll()"
    >

        {{-- Floating Add Employee button (employees page only) --}}
        @if ($canManage && request()->routeIs('employees.index'))
            <button type="button" @click="openCreate()"
                x-show="!formOpen && !detail" x-transition.opacity
                class="fixed bottom-6 right-6 z-50 inline-flex cursor-pointer items-center gap-2 rounded-lg bg-[#b22222] px-4 py-3 font-bold text-white shadow-[0_6px_20px_rgba(178,34,34,0.4)] transition hover:-translate-y-0.5 hover:bg-[#8b1a1a] hover:shadow-[0_8px_24px_rgba(178,34,34,0.5)]">
                <i class="fa-solid fa-user-plus"></i> Add Employee
            </button>
        @endif

        {{-- Flash message --}}
        @if (session('success'))
            <div class="mb-5 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <section aria-label="Employee directory" class="flex flex-col gap-5">

            {{-- Toolbar: search + filters (runs in the database) --}}
            <form method="GET" action="{{ route('employees.index') }}" x-ref="filters"
                class="flex flex-wrap items-end gap-3.5 rounded-[20px] bg-white p-[18px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

                <label for="employeeSearch" class="relative flex min-w-[240px] flex-[1_1_280px] flex-col gap-2 text-sm font-bold text-gray-600">
                    <i class="fa-solid fa-magnifying-glass absolute bottom-[14px] left-[14px] text-gray-400"></i>
                    <input type="search" id="employeeSearch" name="search" value="{{ request('search') }}"
                        placeholder="Search employee name, ID or position"
                        @input.debounce.400ms="$refs.filters.submit()"
                        x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length) }"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-3 pl-[42px] pr-3.5 text-gray-900 focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10">
                </label>

                {{-- Department filter: Elementary / High School / Senior High School + departments stored in SQL --}}
                <label for="departmentFilter" class="flex min-w-[180px] flex-col gap-2 text-sm font-bold text-gray-600">
                    <span>Department</span>
                    <select id="departmentFilter" name="department" @change="$refs.filters.submit()" class="{{ $inputClass }}">
                        <option value="">All Department</option>
                        @foreach ($filterDepartments as $value => $label)
                            <option value="{{ $value }}" @selected(request('department') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                {{-- Role type filter --}}
                <label for="roleFilter" class="flex min-w-[180px] flex-col gap-2 text-sm font-bold text-gray-600">
                    <span>Role Type</span>
                    <select id="roleFilter" name="role_type" @change="$refs.filters.submit()" class="{{ $inputClass }}">
                        <option value="">All Roles</option>
                        @foreach ($filterRoleTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('role_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                @if (request()->filled('search') || request()->filled('department') || request()->filled('role_type'))
                    <a href="{{ route('employees.index') }}"
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

        {{-- ============ Details modal ============ --}}
        <div x-show="detail" x-cloak x-transition.opacity
            @click.self="detailId = null"
            class="fixed inset-0 z-[60] grid place-items-center bg-[rgba(3,7,18,0.5)] p-4">
            <div role="dialog" aria-modal="true" class="relative max-h-[90vh] w-full max-w-[500px] overflow-y-auto [scrollbar-width:none] rounded-[14px] bg-white px-7 pb-7 pt-9 text-center shadow-[0_20px_50px_rgba(15,23,42,0.14)]">
                <button type="button" @click="detailId = null" aria-label="Close"
                    class="absolute right-3 top-2 cursor-pointer text-3xl leading-none text-gray-500 hover:text-gray-800">&times;</button>

                <template x-if="detail">
                    <div>
                        <div class="mx-auto grid h-[88px] w-[88px] place-items-center rounded-full bg-gradient-to-br from-[#b22222] to-[#dc2626] text-xl font-extrabold text-white" x-text="detail.initials"></div>

                        <h3 class="mt-4 text-lg font-bold" x-text="detail.name"></h3>

                        <div class="mt-2 inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-bold">
                            <span class="text-[#b22222]"
                                x-text="statusLabel(detail.employment_status)"></span>
                            <span x-show="detail.step_label" aria-hidden="true" class="text-red-200">|</span>
                            <span x-show="detail.step_label" class="text-[#b22222]" x-text="detail.step_label"></span>
                        </div>

                        <div class="mt-3 space-y-1.5 text-gray-700">
                            <p><strong>ID:</strong> <span x-text="detail.employee_id"></span></p>
                            <p><strong>Position:</strong> <span x-text="detail.position"></span></p>
                            <p><strong>Department:</strong> <span x-text="detail.department_label || detail.department"></span></p>
                            <p><strong>Role Type:</strong> <span x-text="detail.role_type"></span></p>
                            <p><strong>Email:</strong> <span x-text="detail.email"></span></p>
                            <p><strong>Birthday:</strong> <span x-text="detail.birthday_label"></span></p>
                            <p><strong>Address:</strong> <span x-text="detail.address"></span></p>
                            <p><strong>Emergency Contact:</strong> <span x-text="detail.emergency_name + ' - ' + detail.emergency_contact"></span></p>
                        </div>

                        @if ($canManage)
                            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                                <button type="button" @click="openEdit()"
                                    class="cursor-pointer rounded-lg bg-[#b22222] px-6 py-2.5 font-bold text-white transition hover:bg-[#8b1a1a]">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                </button>

                                <form method="POST" :action="deleteAction()" onsubmit="return confirm('Delete this employee? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="w-full cursor-pointer rounded-lg border border-[#b22222] bg-white px-6 py-2.5 font-bold text-[#b22222] transition hover:bg-red-50">
                                        <i class="fa-solid fa-trash mr-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </template>
            </div>
        </div>

        {{-- ============ Add / Edit modal ============ --}}
        <div x-show="formOpen" x-cloak x-transition.opacity
            class="fixed inset-0 z-[60] grid place-items-center bg-[rgba(3,7,18,0.5)] p-4">
            <div role="dialog" aria-modal="true" class="relative max-h-[92vh] w-full max-w-[800px] overflow-y-auto [scrollbar-width:none] rounded-[14px] bg-white p-6 shadow-[0_20px_50px_rgba(15,23,42,0.14)] sm:p-8">
                <button type="button" @click="formOpen = false" aria-label="Close"
                    class="absolute right-3 top-2 cursor-pointer text-3xl leading-none text-gray-500 hover:text-gray-800">&times;</button>

                <h3 class="mb-5 text-2xl font-extrabold text-[#b22222]" x-text="editingId ? 'Edit Employee' : 'Add Employee'"></h3>

                <form method="POST" :action="formAction()" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" :disabled="!editingId">
                    <input type="hidden" name="_editing_id" :value="editingId ?? ''">
                    <input type="hidden" name="employee_source" x-model="form.employee_source" :disabled="!!editingId">

                    <div x-show="!editingId" class="space-y-4">
                        <div class="grid grid-cols-2 gap-2 rounded-xl bg-gray-100 p-1" role="tablist" aria-label="Employee type">
                            <button type="button" role="tab" @click="selectSource('applicant')"
                                :aria-selected="form.employee_source === 'applicant'"
                                :class="form.employee_source === 'applicant' ? 'bg-white text-[#b22222] shadow-sm' : 'text-gray-600 hover:bg-white/70'"
                                class="rounded-lg px-3 py-2.5 text-sm font-bold transition">
                                Applicant
                            </button>
                            <button type="button" role="tab" @click="selectSource('existing')"
                                :aria-selected="form.employee_source === 'existing'"
                                :class="form.employee_source === 'existing' ? 'bg-white text-[#b22222] shadow-sm' : 'text-gray-600 hover:bg-white/70'"
                                class="rounded-lg px-3 py-2.5 text-sm font-bold transition">
                                Existing Employee
                            </button>
                        </div>
                        <p class="{{ $errClass }}">{{ $errors->first('employee_source') }}</p>
                    </div>

                    {{-- Select an applicant account to convert it into an employee login. --}}
                    <div x-show="!editingId && form.employee_source === 'applicant'">
                        <label for="f_user_id" class="{{ $labelClass }}">Applicant <span class="text-red-600">*</span></label>

                        <select id="f_user_id" name="user_id" x-model="form.user_id" @change="pickApplicant()"
                            :disabled="!!editingId || form.employee_source !== 'applicant'"
                            :required="!editingId && form.employee_source === 'applicant'"
                            class="{{ $inputClass }} cursor-pointer">
                            <option value="" disabled hidden>Select an applicant</option>
                            <template x-for="a in Object.values(applicants)" :key="a.id">
                                <option :value="a.id" :selected="String(a.id) === String(form.user_id)" x-text="a.name + ' (' + a.email + ')'"></option>
                            </template>
                        </select>

                        <p x-show="!hasApplicants" class="mt-1 text-xs font-semibold text-amber-700">
                            No applicants available. Only accounts with the Applicant role and no employee record yet are listed.
                        </p>
                        <p class="{{ $errClass }}">{{ $errors->first('user_id') }}</p>
                    </div>

                    <div>
                        <span class="{{ $labelClass }}">Full Name <span class="text-red-600">*</span></span>
                        <div class="grid grid-cols-[1fr_1fr_1fr_0.65fr] gap-2">
                                <div>
                                    <input id="f_first_name" name="first_name" x-model="form.first_name" placeholder="First Name" required
                                        @input="if (!editingId && form.employee_source === 'applicant') form.email = employeeLoginEmail()"
                                        class="{{ $inputClass }}">
                                    <p class="{{ $errClass }}">{{ $errors->first('first_name') }}</p>
                                </div>
                                <div>
                                    <input id="f_middle_name" name="middle_name" x-model="form.middle_name" placeholder="Middle Name"
                                        class="{{ $inputClass }}">
                                    <p class="{{ $errClass }}">{{ $errors->first('middle_name') }}</p>
                                </div>
                                <div>
                                    <input id="f_last_name" name="last_name" x-model="form.last_name" placeholder="Last Name" required
                                        @input="if (!editingId && form.employee_source === 'applicant') form.email = employeeLoginEmail()"
                                        class="{{ $inputClass }}">
                                    <p class="{{ $errClass }}">{{ $errors->first('last_name') }}</p>
                                </div>
                                <div>
                                    <input id="f_suffix" name="suffix" x-model="form.suffix" placeholder="Suffix (optional)"
                                        :readonly="!editingId && form.employee_source === 'applicant'"
                                        :class="!editingId && form.employee_source === 'applicant' ? 'cursor-not-allowed !bg-gray-100 text-gray-500' : ''"
                                        class="{{ $inputClass }}">
                                    <p class="{{ $errClass }}">{{ $errors->first('suffix') }}</p>
                                </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="f_employee_id" class="{{ $labelClass }}">Employee ID</label>
                            <input id="f_employee_id" name="employee_id" x-model="form.employee_id" placeholder="Leave blank to auto-generate" class="{{ $inputClass }}">
                            <p class="{{ $errClass }}">{{ $errors->first('employee_id') }}</p>
                        </div>
                        <div>
                            <label for="f_step_no" class="{{ $labelClass }}">Step <span class="text-red-600">*</span></label>
                            {{-- Step names come from payroll_steps; wage amounts are managed separately. --}}
                            <select id="f_step_no" name="step_no" x-model="form.step_no" required class="{{ $inputClass }}">
                                <option value="" disabled hidden>Select step</option>
                                @foreach ($payrollSteps as $stepNo => $stepLabel)
                                    <option value="{{ $stepNo }}">{{ $stepLabel }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $errClass }}">{{ $errors->first('step_no') }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="f_department" class="{{ $labelClass }}">Department <span class="text-red-600">*</span></label>
                            <input id="f_department" name="department" x-model="form.department" list="employeeDepartmentOptions"
                                placeholder="Select or type department" maxlength="100" required class="{{ $inputClass }}">
                            <datalist id="employeeDepartmentOptions">
                                @foreach ($formDepartments as $value => $label)
                                    <option value="{{ $value }}" label="{{ $label }}"></option>
                                @endforeach
                            </datalist>
                            <p class="{{ $errClass }}">{{ $errors->first('department') }}</p>
                        </div>
                        <div>
                            <label for="f_position" class="{{ $labelClass }}">Position <span class="text-red-600">*</span></label>
                            <input id="f_position" name="position" x-model="form.position" placeholder="e.g. Mathematics Teacher" required class="{{ $inputClass }}">
                            <p class="{{ $errClass }}">{{ $errors->first('position') }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="f_role_type" class="{{ $labelClass }}">Role Type <span class="text-red-600">*</span></label>
                            {{-- Options = Administrative / Teaching / Maintenance / Support Staff --}}
                            <select id="f_role_type" name="role_type" x-model="form.role_type" required class="{{ $inputClass }}">
                                <option value="" disabled hidden>Select role type</option>
                                @foreach ($roleTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $errClass }}">{{ $errors->first('role_type') }}</p>
                        </div>
                        <div>
                            <label for="f_employment_status" class="{{ $labelClass }}">Employment Status <span class="text-red-600">*</span></label>
                            <select id="f_employment_status" name="employment_status" x-model="form.employment_status" required class="{{ $inputClass }}">
                                <option value="" disabled hidden>Select employment status</option>
                                @foreach ($employmentStatuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $errClass }}">{{ $errors->first('employment_status') }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="f_birthday" class="{{ $labelClass }}">Birthday <span class="text-red-600">*</span></label>
                            <input id="f_birthday" type="date" name="birthday" x-model="form.birthday" max="{{ now()->subDay()->toDateString() }}" required class="{{ $inputClass }}">
                            <p class="{{ $errClass }}">{{ $errors->first('birthday') }}</p>
                        </div>
                        <div>
                            <label for="f_gender" class="{{ $labelClass }}">Gender</label>
                            <select id="f_gender" name="gender" x-model="form.gender" class="{{ $inputClass }}">
                                <option value="">Select gender</option>
                                @foreach (['Male', 'Female', 'Other'] as $gender)
                                    <option value="{{ $gender }}">{{ $gender }}</option>
                                @endforeach
                            </select>
                            <p class="{{ $errClass }}">{{ $errors->first('gender') }}</p>
                        </div>
                    </div>

                    <div>
                        <label for="f_email" class="{{ $labelClass }}">Email <span class="text-red-600">*</span></label>
                        <input id="f_email" type="email" name="email" x-model="form.email" placeholder="jsurname@smms.edu" required
                            :readonly="!editingId && form.employee_source === 'applicant'"
                            :class="!editingId && form.employee_source === 'applicant' ? 'cursor-not-allowed !bg-gray-100 text-gray-500' : ''"
                            class="{{ $inputClass }}">
                        <p class="{{ $errClass }}">{{ $errors->first('email') }}</p>
                    </div>

                    <div>
                        <span class="{{ $labelClass }}">Address <span class="text-red-600">*</span></span>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <input id="f_address_line" name="address_line" x-model="form.address_line" placeholder="House/Lot/Block No./Street" required class="{{ $inputClass }}">
                                <p class="{{ $errClass }}">{{ $errors->first('address_line') }}</p>
                            </div>
                            <div>
                                <input id="f_address_landmark" name="address_landmark" x-model="form.address_landmark" placeholder="Subdivision/Building/Landmark (optional)" class="{{ $inputClass }}">
                                <p class="{{ $errClass }}">{{ $errors->first('address_landmark') }}</p>
                            </div>
                            <div>
                                <input id="f_barangay" name="barangay" x-model="form.barangay" placeholder="Barangay" required class="{{ $inputClass }}">
                                <p class="{{ $errClass }}">{{ $errors->first('barangay') }}</p>
                            </div>
                            <div>
                                <input id="f_city_municipality" name="city_municipality" x-model="form.city_municipality" placeholder="City / Municipality" required class="{{ $inputClass }}">
                                <p class="{{ $errClass }}">{{ $errors->first('city_municipality') }}</p>
                            </div>
                            <div>
                                <input id="f_province" name="province" x-model="form.province" placeholder="Province" required class="{{ $inputClass }}">
                                <p class="{{ $errClass }}">{{ $errors->first('province') }}</p>
                            </div>
                            <div>
                                <input id="f_zip_code" name="zip_code" x-model="form.zip_code" inputmode="numeric" pattern="[0-9]{4}" maxlength="4"
                                    @input="$el.value = $el.value.replace(/\D/g, '')"
                                    placeholder="ZIP Code" required class="{{ $inputClass }}">
                                <p class="{{ $errClass }}">{{ $errors->first('zip_code') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="f_emergency_name" class="{{ $labelClass }}">Emergency Contact Name <span class="text-red-600">*</span></label>
                            <input id="f_emergency_name" name="emergency_name" x-model="form.emergency_name" placeholder="e.g. Ana Santos" required class="{{ $inputClass }}">
                            <p class="{{ $errClass }}">{{ $errors->first('emergency_name') }}</p>
                        </div>
                        <div>
                            <label for="f_emergency_contact" class="{{ $labelClass }}">Emergency Contact Number <span class="text-red-600">*</span></label>
                            <input id="f_emergency_contact" type="tel" name="emergency_contact" x-model="form.emergency_contact" placeholder="+639XXXXXXXXX" required class="{{ $inputClass }}">
                            <p class="{{ $errClass }}">{{ $errors->first('emergency_contact') }}</p>
                        </div>
                    </div>

                    <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">
                        <i class="fa-solid fa-lock mr-1"></i> Address and emergency contact details are stored encrypted (AES-256).
                    </p>

                    <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                        <button type="button" @click="formOpen = false"
                            class="cursor-pointer rounded-lg bg-gray-100 px-6 py-3 font-bold text-gray-800 transition hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" :disabled="!editingId && form.employee_source === 'applicant' && !hasApplicants"
                            class="cursor-pointer rounded-lg bg-[#b22222] px-6 py-3 font-bold text-white transition hover:bg-[#8b1a1a] hover:shadow-[0_6px_20px_rgba(178,34,34,0.4)] disabled:cursor-not-allowed disabled:opacity-60">
                            <span x-text="editingId ? 'Save Changes' : 'Add Employee'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function employeeDirectory(cfg) {
            const defaultStep = String(cfg.defaultStep ?? '');

            const blank = () => ({
                employee_source: 'applicant',
                user_id: '',
                employee_id: '', first_name: '', middle_name: '', last_name: '', suffix: '',
                position: '', department: '', role_type: '',
                employment_status: 'regular',
                step_no: defaultStep,
                email: '', birthday: '', gender: '',
                address_line: '', address_landmark: '', barangay: '', city_municipality: '', province: '', zip_code: '',
                emergency_name: '', emergency_contact: '',
            });

            return {
                employees: cfg.employees,
                applicants: cfg.applicants || {},   // { userId: { id, name, email } }
                statuses: cfg.statuses || {},       // { regular: 'Regular', ... }
                detailId: null,
                formOpen: false,
                editingId: null,
                form: blank(),

                get detail() {
                    return this.detailId ? this.employees[this.detailId] : null;
                },

                get hasApplicants() {
                    return Object.keys(this.applicants).length > 0;
                },

                init() {
                    // Validation failed on the server: reopen the form with the typed values
                    if (cfg.reopen) {
                        this.editingId = cfg.editingId || null;
                        this.form = Object.assign(blank(), cfg.old);
                        this.form.employee_source = this.form.employee_source === 'existing' ? 'existing' : 'applicant';
                        this.form.step_no = String(this.form.step_no ?? defaultStep);
                        this.formOpen = true;
                    }
                },

                // Readable label for a stored employment status
                statusLabel(value) {
                    return this.statuses[value] || value || '—';
                },

                // Fill the selected applicant's application details and employee login email.
                pickApplicant() {
                    const a = this.applicants[this.form.user_id];
                    Object.assign(this.form, a
                        ? {
                            first_name: a.first_name || this.splitName(a.name).first_name,
                            middle_name: a.middle_name || this.splitName(a.name).middle_name,
                            last_name: a.last_name || this.splitName(a.name).last_name,
                            suffix: a.suffix || this.splitName(a.name).suffix,
                        }
                        : this.splitName(''));
                    this.form.email = a ? this.employeeLoginEmail() : '';
                    this.form.birthday = a?.birthday || '';
                    this.form.gender = a?.gender || '';
                    Object.assign(this.form, a?.address || this.splitAddress(''));
                },

                employeeLoginEmail() {
                    const normalize = value => String(value || '')
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLowerCase()
                        .replace(/[^a-z0-9]/g, '');
                    const initial = normalize(this.form.first_name).slice(0, 1);
                    const surname = normalize(this.form.last_name);

                    return initial && surname ? `${initial}${surname}@smms.edu` : '';
                },

                splitName(value) {
                    const parts = String(value || '').trim().split(/\s+/).filter(Boolean);
                    let suffix = '';
                    if (parts.length > 1 && /^(jr\.?|sr\.?|ii|iii|iv|v)$/i.test(parts[parts.length - 1].replace(/,$/, ''))) {
                        suffix = parts.pop().replace(/,$/, '');
                    }

                    return {
                        first_name: parts.shift() || '',
                        middle_name: parts.length > 1 ? parts.slice(0, -1).join(' ') : '',
                        last_name: parts.length ? parts[parts.length - 1] : '',
                        suffix,
                    };
                },

                splitAddress(value) {
                    const parts = String(value || '').split(',').map(part => part.trim());
                    if (parts.length === 6 && /^\d{4}$/.test(parts[5])) {
                        return {
                            address_line: parts[0],
                            address_landmark: parts[1],
                            barangay: parts[2],
                            city_municipality: parts[3],
                            province: parts[4],
                            zip_code: parts[5],
                        };
                    }

                    return {
                        address_line: String(value || ''),
                        address_landmark: '',
                        barangay: '',
                        city_municipality: '',
                        province: '',
                        zip_code: '',
                    };
                },

                selectSource(source) {
                    if (this.form.employee_source === source) return;
                    this.form.employee_source = source;
                    this.form.user_id = '';
                    Object.assign(this.form, this.splitName(''));
                    this.form.email = '';
                },

                openDetail(id) { this.detailId = id; },

                closeAll() {
                    this.detailId = null;
                    this.formOpen = false;
                },

                openCreate() {
                    this.editingId = null;
                    this.form = blank();
                    this.detailId = null;
                    this.formOpen = true;
                },

                openEdit() {
                    const e = this.detail;
                    if (!e) return;
                    this.form = {
                        employee_source: 'existing',
                        user_id: '',
                        employee_id: e.employee_id, ...this.splitName(e.name), position: e.position,
                        department: e.department, role_type: e.role_type,
                        employment_status: e.employment_status || 'regular',
                        // Use the employee's saved step (needs 'step_no' in the card data)
                        step_no: String(e.step_no ?? defaultStep),
                        email: e.email,
                        birthday: e.birthday, gender: e.gender || '',
                        ...this.splitAddress(e.address),
                        emergency_name: e.emergency_name, emergency_contact: e.emergency_contact,
                    };
                    this.editingId = e.id;
                    this.detailId = null;
                    this.formOpen = true;
                },

                formAction() {
                    return this.editingId ? cfg.updateUrl.replace('__ID__', this.editingId) : cfg.storeUrl;
                },

                deleteAction() {
                    return cfg.destroyUrl.replace('__ID__', this.detailId);
                },
            };
        }
    </script>
{{-- resources/views/profile/partials/update-account-form.blade.php --}}
{{-- Needs: $employee, $stepName (passed from ProfileController@edit) --}}
@php
    $fullName = $employee->name ?? '';
    $initials = collect(preg_split('/\s+/', trim($fullName)))
        ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '--';

    $statusKey = strtolower(str_replace(['-', '_'], ' ', trim((string) $employee->employment_status)));
    $employmentStatus = match ($statusKey) {
        ''            => 'Not set',
        'regular'     => 'Regular',
        'non regular' => 'Non-regular',
        default       => ucfirst($statusKey),
    };

    $startTab = match (true) {
        collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'education')) => 'education',
        $errors->hasAny(['emergency_name', 'emergency_contact'])                      => 'contact',
        $errors->hasAny(['sss_number', 'philhealth_number', 'pagibig_number', 'tin']) => 'ids',
        default                                                                       => 'profile',
    };

    $tabs = [
        'profile'   => ['Personal Information',   'fa-regular fa-user'],
        'education' => ['Educational Background', 'fa-solid fa-graduation-cap'],
        'contact'   => ['Emergency Contact',      'fa-regular fa-address-card'],
        'ids'       => ['Government IDs',         'fa-solid fa-id-card'],
    ];

    $education = $employee->education ?? [];

    $genders       = \App\Http\Controllers\ProfileController::GENDERS;
    $civilStatuses = \App\Http\Controllers\ProfileController::CIVIL_STATUSES;
@endphp

<section aria-labelledby="accountProfileHeading"
    x-data="{
        editing: @js($errors->any()),
        tab: @js($startTab),
        hasErrors: @js($errors->any()),
        cancel() {
            if (this.hasErrors) { window.location = @js(route('profile.edit')); return; }
            this.$refs.form.reset();
            this.editing = false;
        },
    }"
    @keydown.escape.window="editing && cancel()">

    @if (session('status') === 'profile-updated')
        <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800" role="status">
            {{ __('Your profile has been saved.') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ $errors->first() }}</div>
    @endif

    {{-- Identity + edit button --}}
    <div class="mb-7 flex flex-col gap-4 border-b border-gray-200 pb-[22px] sm:flex-row sm:items-start sm:justify-between sm:gap-5">
        <div class="flex min-w-0 items-center gap-[15px]">
            <span class="grid h-16 w-16 flex-none place-items-center rounded-full bg-[#b22222] text-base font-extrabold text-white sm:h-20 sm:w-20 sm:text-[1.1rem]" aria-hidden="true">{{ $initials }}</span>
            <div class="min-w-0">
                <h3 id="accountProfileHeading" class="mb-1 break-words text-2xl font-bold leading-tight text-gray-900 sm:text-3xl">{{ $fullName ?: 'My Account' }}</h3>
                <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-2 text-[0.88rem] font-semibold text-gray-500" aria-label="Employment details">
                    <span>{{ $employee->employee_id }}</span>
                    <span class="text-gray-400" aria-hidden="true">|</span>
                    <span>{{ $employee->position ?: 'No position' }}</span>
                    <span class="text-gray-400" aria-hidden="true">|</span>
                    <span>{{ $employmentStatus }}</span>
                    <span class="text-gray-400" aria-hidden="true">|</span>
                    <span>{{ $stepName ?: 'Not set' }}</span>
                </div>
            </div>
        </div>

        <button type="button" x-show="!editing" @click="editing = true"
            class="min-h-[44px] flex-none cursor-pointer self-start whitespace-nowrap rounded-md px-2.5 py-2 font-bold text-[#b22222] transition hover:bg-gray-50 hover:text-[#8d1111]">
            <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
        </button>
    </div>

    <form method="POST" action="{{ route('profile.updateAccount') }}" x-ref="form" novalidate>
        @csrf
        @method('PATCH')

        {{-- Tabs --}}
        <div class="-mx-1 mb-6 flex overflow-x-auto border-b border-gray-200 px-1" role="tablist" aria-label="Account information">
            @foreach ($tabs as $key => [$label, $icon])
                <button type="button" role="tab" @click="tab = '{{ $key }}'"
                    :aria-selected="tab === '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'border-[#b22222] text-[#b22222]' : 'border-transparent text-gray-500 hover:text-[#b22222]'"
                    class="inline-flex min-h-[42px] shrink-0 grow cursor-pointer items-center justify-center gap-[9px] whitespace-nowrap border-b-2 px-3.5 py-2 text-[0.92rem] font-bold transition">
                    <i class="{{ $icon }}" aria-hidden="true"></i> {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Personal information --}}
        <section x-show="tab === 'profile'" role="tabpanel" class="min-h-[200px] py-2">
            <p class="mb-[22px] text-[0.9rem] text-gray-500">Review and update your personal details.</p>
            <div class="grid grid-cols-1 gap-[18px] sm:grid-cols-2">
                @include('profile.partials.account-field', ['key' => 'name', 'label' => 'Full Name', 'value' => $employee->name, 'wide' => true, 'attrs' => 'required maxlength="120" autocomplete="name"'])

                @include('profile.partials.account-field', ['key' => 'birthday', 'label' => 'Date of Birth', 'value' => $employee->birthday?->format('Y-m-d'), 'type' => 'date', 'attrs' => 'required max="' . now()->subYears(18)->toDateString() . '" autocomplete="bday"'])
                @include('profile.partials.account-field', ['key' => 'gender', 'label' => 'Gender', 'value' => $employee->gender, 'placeholder' => 'Select gender', 'options' => $genders])
                @include('profile.partials.account-field', ['key' => 'civil_status', 'label' => 'Civil Status', 'value' => $employee->civil_status, 'placeholder' => 'Select civil status', 'options' => $civilStatuses])

                @include('profile.partials.account-field', ['key' => 'email', 'label' => 'Email Address', 'value' => $employee->email, 'type' => 'email', 'attrs' => 'required maxlength="254" autocomplete="email"'])
                @include('profile.partials.account-field', ['key' => 'phone', 'label' => 'Contact Number', 'value' => $employee->phone, 'type' => 'tel', 'attrs' => 'inputmode="tel" maxlength="20" autocomplete="tel"'])

                @include('profile.partials.account-field', ['key' => 'address', 'label' => 'Home Address', 'value' => $employee->address, 'wide' => true, 'multiline' => true, 'attrs' => 'maxlength="500" autocomplete="street-address"'])
            </div>
        </section>

        {{-- Educational background --}}
        <section x-show="tab === 'education'" x-cloak role="tabpanel" class="min-h-[200px] py-2">
            <p class="mb-[22px] text-[0.9rem] text-gray-500">Add your education history, starting with your highest level.</p>
            <div class="grid gap-4">
                @foreach (\App\Models\Employee::EDUCATION_LEVELS as $level => $config)
                    <fieldset class="min-w-0 rounded-[10px] border border-gray-200 p-4 sm:p-[18px]">
                        <legend class="px-2 text-[0.95rem] font-bold text-[#b22222]">{{ $config['label'] }}</legend>
                        <div class="grid grid-cols-1 gap-[18px]">
                            @foreach ($config['fields'] as $field => [$label, $hint, $max])
                                @include('profile.partials.account-field', [
                                    'key'         => "education.$level.$field",
                                    'inputName'   => "education[$level][$field]",
                                    'label'       => $label,
                                    'value'       => $education[$level][$field] ?? '',
                                    'placeholder' => $hint,
                                    'attrs'       => "maxlength=\"$max\"",
                                ])
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
        </section>

        {{-- Emergency contact --}}
        <section x-show="tab === 'contact'" x-cloak role="tabpanel" class="min-h-[200px] py-2">
            <p class="mb-[22px] text-[0.9rem] text-gray-500">Provide someone the school can contact in case of an emergency.</p>
            <div class="grid grid-cols-1 gap-[18px] sm:grid-cols-2">
                @include('profile.partials.account-field', ['key' => 'emergency_name', 'label' => 'Emergency Contact Name', 'value' => $employee->emergency_name, 'attrs' => 'maxlength="120" autocomplete="off"'])
                @include('profile.partials.account-field', ['key' => 'emergency_contact', 'label' => 'Emergency Contact Number', 'value' => $employee->emergency_contact, 'type' => 'tel', 'attrs' => 'inputmode="tel" maxlength="20" autocomplete="off"'])
            </div>
        </section>

        {{-- Government IDs --}}
        <section x-show="tab === 'ids'" x-cloak role="tabpanel" class="min-h-[200px] py-2">
            <p class="mb-[22px] text-[0.9rem] text-gray-500">Manage your government identification numbers.</p>
            <div class="grid grid-cols-1 gap-[18px] sm:grid-cols-2">
                @include('profile.partials.account-field', ['key' => 'sss_number', 'label' => 'SSS Number', 'value' => $employee->sss_number, 'attrs' => 'maxlength="32" autocomplete="off"'])
                @include('profile.partials.account-field', ['key' => 'philhealth_number', 'label' => 'PhilHealth Number', 'value' => $employee->philhealth_number, 'attrs' => 'maxlength="32" autocomplete="off"'])
                @include('profile.partials.account-field', ['key' => 'pagibig_number', 'label' => 'Pag-IBIG Number', 'value' => $employee->pagibig_number, 'attrs' => 'maxlength="32" autocomplete="off"'])
                @include('profile.partials.account-field', ['key' => 'tin', 'label' => 'TIN', 'value' => $employee->tin, 'attrs' => 'maxlength="32" autocomplete="off"'])
            </div>
        </section>

        {{-- Actions --}}
        <div x-show="editing" x-cloak
            class="mt-[26px] flex flex-col-reverse items-stretch justify-end gap-2.5 border-t border-gray-200 pt-5 sm:flex-row sm:items-center">
            <button type="button" @click="cancel()"
                class="min-h-[44px] cursor-pointer rounded-md px-2.5 py-2 font-bold text-gray-500 transition hover:bg-gray-50 hover:text-[#8d1111]">Cancel</button>
            <button type="submit"
                class="min-h-[44px] w-full cursor-pointer rounded-[9px] bg-[#b22222] px-5 py-2.5 font-extrabold text-white transition hover:bg-[#8d1111] sm:w-auto">Save changes</button>
        </div>
    </form>
</section>
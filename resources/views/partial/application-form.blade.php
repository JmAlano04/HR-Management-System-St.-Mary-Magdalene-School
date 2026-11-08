<x-slot name="title">My Application</x-slot>

    @php
        // Passed from ApplicationController@index (null if the applicant hasn't submitted yet)
        $application = $application ?? null;
<<<<<<< HEAD
        $status = $application->status ?? null;
        $startStep = ! $application ? 1 : ($application->interview_completed_at ? 4 : 3);
=======
        $status = $application->status ?? null; // pending | qualified | rejected
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        // Titles of the currently open positions (from the job_openings table)
        $jobOpenings = $jobOpenings ?? collect();

        $inputClass = 'min-h-[50px] w-full rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-[15px] transition hover:border-gray-300 focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10';
<<<<<<< HEAD
        $initialNameParts = preg_split('/\s+/', trim((string) ($user->name ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initialSuffix = '';
        if (count($initialNameParts) > 1 && preg_match('/^(jr\.?|sr\.?|ii|iii|iv|v)$/i', rtrim(end($initialNameParts), ','))) {
            $initialSuffix = preg_replace('/,$/', '', (string) array_pop($initialNameParts));
        }
        $initialFirstName = array_shift($initialNameParts) ?? '';
        $initialLastName = count($initialNameParts) > 1 ? array_pop($initialNameParts) : (array_pop($initialNameParts) ?? '');
        $initialMiddleName = implode(' ', $initialNameParts);
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    @endphp

    <div
        class="bg-gradient-to-br from-[#f5f7fa] to-[#f5f7fa] px-5 py-10"
<<<<<<< HEAD
        x-data="applicationForm({{ $startStep }}, {{ $application ? 'true' : 'false' }}, {{ $jobOpenings->isNotEmpty() ? 'true' : 'false' }})"
=======
        x-data="applicationForm({{ $application ? 3 : 1 }}, {{ $application ? 'true' : 'false' }}, {{ $jobOpenings->isNotEmpty() ? 'true' : 'false' }})"
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    >
        <div class="mx-auto max-w-[850px] rounded-2xl bg-white p-5 shadow-[0_20px_60px_rgba(0,0,0,0.15)] sm:p-12">

            {{-- Header --}}
            <div class="mb-10 text-center">
                <h1 class="mb-2 text-2xl font-extrabold text-[#b22222] sm:text-4xl">Job Application Form</h1>
                <p class="text-base font-medium text-gray-500 sm:text-lg">Join our team at St. Mary Magdalene School</p>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Progress indicator --}}
            <div class="mb-12 flex items-center justify-between gap-1">
                @foreach (['Basic Info', 'Documents', 'Interview', 'Status'] as $i => $label)
<<<<<<< HEAD
                    @php
                        $n = $i + 1;
                        $stepCircleClass = match (true) {
                            $n === 4 && in_array($status, ['qualified', 'approved'], true) => 'bg-[#28a745] text-white shadow-[0_6px_20px_rgba(40,167,69,0.25)]',
                            $n === 4 && in_array($status, ['hr_rejected', 'rejected'], true) => 'bg-[#dc3545] text-white shadow-[0_6px_20px_rgba(220,53,69,0.25)]',
                            default => '',
                        };
                    @endphp
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full text-base font-bold transition-all duration-300 sm:h-12 sm:w-12 sm:text-lg {{ $stepCircleClass }}"
                            @if ($stepCircleClass === '')
                                :class="step > {{ $n }} ? 'bg-[#28a745] text-white shadow-[0_6px_20px_rgba(40,167,69,0.25)]' : (step === {{ $n }} ? 'bg-[#b22222] text-white shadow-[0_6px_20px_rgba(178,34,34,0.35)]' : 'bg-gray-200 text-gray-400')"
                            @endif
=======
                    @php $n = $i + 1; @endphp
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full text-base font-bold transition-all duration-300 sm:h-12 sm:w-12 sm:text-lg"
                            :class="step === {{ $n }}
                                ? 'scale-110 bg-[#b22222] text-white shadow-[0_6px_20px_rgba(178,34,34,0.35)]'
                                : (step > {{ $n }}
                                    ? 'bg-[#28a745] text-white shadow-[0_6px_20px_rgba(40,167,69,0.25)]'
                                    : 'bg-gray-200 text-gray-400')"
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                        >{{ $n }}</span>
                        <span
                            class="text-center text-[11px] font-semibold transition sm:text-[13px]"
                            :class="step === {{ $n }} ? 'font-bold text-[#b22222]' : (step > {{ $n }} ? 'font-bold text-[#28a745]' : 'text-gray-400')"
                        >{{ $label }}</span>
                    </div>
                    @if ($n < 4)
                        <div class="mx-1 h-[3px] flex-1 transition-all" :class="step > {{ $n }} ? 'bg-[#28a745]' : 'bg-gray-200'"></div>
                    @endif
                @endforeach
            </div>

            <form
                x-ref="form"
                method="POST"
                action="{{ route('applications.store') }}"
                enctype="multipart/form-data"
                novalidate
            >
                @csrf

                {{-- ============ STEP 1: Personal Information ============ --}}
                <div x-show="step === 1" x-ref="step1" x-transition.opacity.duration.300ms>
                    <div class="mb-8 border-b-2 border-gray-100 pb-5">
                        <h2 class="mb-2 text-xl font-extrabold text-[#b22222] sm:text-[28px]">Step 1: Personal Information</h2>
                        <p class="text-[15px] text-gray-500">Please provide your contact details and position information</p>
                    </div>

                    <div class="space-y-6">
                        {{-- Full name --}}
                        <div>
<<<<<<< HEAD
                            <label class="mb-2 block font-semibold">Full Name <span class="font-bold text-red-600">*</span></label>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $initialFirstName) }}" required maxlength="75"
                                        autocomplete="given-name" placeholder="First Name" class="{{ $inputClass }}">
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('first_name') }}</p>
                                </div>
                                <div>
                                    <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name', $initialMiddleName) }}" maxlength="75"
                                        placeholder="Middle Name (optional)" class="{{ $inputClass }}">
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('middle_name') }}</p>
                                </div>
                                <div>
                                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $initialLastName) }}" required maxlength="75"
                                        autocomplete="family-name" placeholder="Last Name" class="{{ $inputClass }}">
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('last_name') }}</p>
                                </div>
                                <div>
                                    <input type="text" id="suffix" name="suffix" value="{{ old('suffix', $initialSuffix) }}" maxlength="20"
                                        placeholder="Suffix (optional, e.g. Jr.)" class="{{ $inputClass }}">
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('suffix') }}</p>
                                </div>
                            </div>
                            <p class="mt-1 text-[13px] text-gray-500">Enter your legal name as it appears on your documents</p>
=======
                            <label for="fullname" class="mb-2 block font-semibold">Full Name <span class="font-bold text-red-600">*</span></label>
                            <input type="text" id="fullname" name="fullname" value="{{ old('fullname', $user->name ?? '') }}" required
                                placeholder="Enter your complete name" class="{{ $inputClass }}">
                            <p class="mt-1 text-[13px] text-gray-500">Your legal first and last name</p>
                            <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('fullname') }}</p>
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                        </div>

                        {{-- Email + Phone --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="email" class="mb-2 block font-semibold">Email Address <span class="font-bold text-red-600">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                                    placeholder="your.email@example.com" class="{{ $inputClass }}">
                                <p class="mt-1 text-[13px] text-gray-500">We'll use this to contact you</p>
                                <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('email') }}</p>
                            </div>
                            <div>
                                <label for="phone" class="mb-2 block font-semibold">Phone Number <span class="font-bold text-red-600">*</span></label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required
                                    inputmode="numeric" pattern="[0-9]{11}" maxlength="11" placeholder="09XXXXXXXXX"
                                    @input="$el.value = $el.value.replace(/\D/g, '')" class="{{ $inputClass }}">
                                <p class="mt-1 text-[13px] text-gray-500">11-digit mobile number</p>
                                <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('phone') }}</p>
                            </div>
                        </div>

                        {{-- Address --}}
                        <div>
<<<<<<< HEAD
                            <label class="mb-2 block font-semibold">Home Address <span class="font-bold text-red-600">*</span></label>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <input type="text" id="address_line" name="address_line" value="{{ old('address_line') }}" required maxlength="160"
                                        autocomplete="address-line1" placeholder="House/Lot/Block No./Street" class="{{ $inputClass }}">
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('address_line') }}</p>
                                </div>
                                <div>
                                    <input type="text" id="address_landmark" name="address_landmark" value="{{ old('address_landmark') }}" maxlength="160"
                                        placeholder="Subdivision/Building/Landmark (optional)" class="{{ $inputClass }}">
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('address_landmark') }}</p>
                                </div>
                                <div>
                                    <input type="text" id="barangay" name="barangay" value="{{ old('barangay') }}" required maxlength="100"
                                        autocomplete="address-level3" placeholder="Barangay" class="{{ $inputClass }}">
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('barangay') }}</p>
                                </div>
                                <div>
                                    <input type="text" id="city_municipality" name="city_municipality" value="{{ old('city_municipality') }}" required maxlength="100"
                                        autocomplete="address-level2" placeholder="City / Municipality" class="{{ $inputClass }}">
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('city_municipality') }}</p>
                                </div>
                                <div>
                                    <input type="text" id="province" name="province" value="{{ old('province') }}" required maxlength="100"
                                        autocomplete="address-level1" placeholder="Province" class="{{ $inputClass }}">
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('province') }}</p>
                                </div>
                                <div>
                                    <input type="text" id="zip_code" name="zip_code" value="{{ old('zip_code') }}" required inputmode="numeric" pattern="[0-9]{4}" maxlength="4"
                                        @input="$el.value = $el.value.replace(/\D/g, '')"
                                        autocomplete="postal-code" placeholder="ZIP Code" class="{{ $inputClass }}">
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('zip_code') }}</p>
                                </div>
                            </div>
                            <p class="mt-1 text-[13px] text-gray-500">Where HR can reach you if needed</p>
=======
                            <label for="home_address" class="mb-2 block font-semibold">Home Address <span class="font-bold text-red-600">*</span></label>
                            <input type="text" id="home_address" name="home_address" value="{{ old('home_address') }}" required
                                placeholder="House number, street, city, province" class="{{ $inputClass }}">
                            <p class="mt-1 text-[13px] text-gray-500">Where HR can reach you if needed</p>
                            <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('home_address') }}</p>
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                        </div>

                        {{-- Birth date + Gender --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="birth_date" class="mb-2 block font-semibold">Birth Date <span class="font-bold text-red-600">*</span></label>
                                <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date') }}" required
                                    max="{{ now()->subYears(18)->toDateString() }}" class="{{ $inputClass }}">
                                <p class="mt-1 text-[13px] text-gray-500">You must be at least 18 years old</p>
                                <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('birth_date') }}</p>
                            </div>
                            <div>
                                <label for="gender" class="mb-2 block font-semibold">Gender <span class="font-bold text-red-600">*</span></label>
                                <select id="gender" name="gender" required class="{{ $inputClass }}">
                                    <option value="" disabled @selected(! old('gender')) hidden>Select Gender</option>
                                    @foreach (['Male', 'Female', 'Other'] as $g)
                                        <option value="{{ $g }}" @selected(old('gender') === $g)>{{ $g }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-[13px] text-gray-500">Select your gender</p>
                                <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('gender') }}</p>
                            </div>
                        </div>

                        {{-- Civil status + Education --}}
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="civil_status" class="mb-2 block font-semibold">Civil Status <span class="font-bold text-red-600">*</span></label>
                                <select id="civil_status" name="civil_status" required class="{{ $inputClass }}">
                                    <option value="" disabled @selected(! old('civil_status')) hidden>Select Status</option>
                                    @foreach (['Single', 'Married', 'Widowed', 'Separated', 'Divorced'] as $c)
                                        <option value="{{ $c }}" @selected(old('civil_status') === $c)>{{ $c }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-[13px] text-gray-500">Choose your civil status</p>
                                <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('civil_status') }}</p>
                            </div>
                            <div>
                                <label for="education" class="mb-2 block font-semibold">Highest Education <span class="font-bold text-red-600">*</span></label>
                                <select id="education" name="education" required class="{{ $inputClass }}">
                                    <option value="" disabled @selected(! old('education')) hidden>Select Education</option>
                                    @foreach (['Elementary', 'High School', 'Senior High School', 'Vocational', 'Associate Degree', "Bachelor's Degree", "Master's Degree"] as $e)
                                        <option value="{{ $e }}" @selected(old('education') === $e)>{{ $e }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-[13px] text-gray-500">Select your highest academic attainment</p>
                                <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('education') }}</p>
                            </div>
                        </div>

                        {{-- Position (from the currently available job openings) --}}
                        <div>
                            <label for="position" class="mb-2 block font-semibold">Position Applied For <span class="font-bold text-red-600">*</span></label>

                            @if ($jobOpenings->isEmpty())
                                <select id="position" name="position" disabled
                                    class="{{ $inputClass }} cursor-not-allowed bg-gray-50 text-gray-400">
                                    <option value="">No open positions available</option>
                                </select>
                                <p class="mt-1 text-[13px] font-semibold text-amber-700">
                                    There are no open positions right now. Please check back later.
                                </p>
                            @else
                                <select id="position" name="position" required class="{{ $inputClass }}">
                                    <option value="" disabled @selected(! old('position')) hidden>Select a position</option>
                                    @foreach ($jobOpenings as $title)
                                        <option value="{{ $title }}" @selected(old('position') === $title)>{{ $title }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-[13px] text-gray-500">Choose from available positions</p>
                            @endif

                            <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first('position') }}</p>
                        </div>
                    </div>
                </div>

                {{-- ============ STEP 2: Documents ============ --}}
                <div x-show="step === 2" x-ref="step2" x-cloak x-transition.opacity.duration.300ms>
                    <div class="mb-8 border-b-2 border-gray-100 pb-5">
                        <h2 class="mb-2 text-xl font-extrabold text-[#b22222] sm:text-[28px]">Step 2: Upload Required Documents</h2>
                        <p class="text-[15px] leading-relaxed text-gray-500">Submit the documents needed to evaluate your application. All files must be in PDF format and no larger than 5MB each.</p>
                    </div>

                    <div class="space-y-6">
                        @foreach ([
                            ['resume',      '📄', 'Resume or Curriculum Vitae', 'Upload your professional resume or CV highlighting your qualifications, experience, and skills.'],
                            ['coverletter', '✉️', 'Cover Letter',               "Write a compelling cover letter explaining why you're interested in this position and what you can contribute to our school."],
                            ['appletter',   '📋', 'Application Letter',         'Submit your formal application letter addressed to the hiring manager.'],
                        ] as [$name, $icon, $label, $desc])
                            <div class="flex flex-col gap-4 rounded-[10px] border-2 border-dashed border-gray-200 bg-[#f9fbfc] p-5 transition hover:border-[#b22222] hover:bg-[#fff9f9] sm:flex-row sm:gap-5">
                                <div class="min-w-[50px] text-3xl sm:text-center">{{ $icon }}</div>
                                <div class="flex-1">
                                    <label for="{{ $name }}" class="mb-1 block text-[15px] font-semibold">{{ $label }} <span class="font-bold text-red-600">*</span></label>
                                    <p class="mb-4 text-sm leading-relaxed text-gray-500">{{ $desc }}</p>
                                    <input type="file" id="{{ $name }}" name="{{ $name }}" accept=".pdf,application/pdf" required
                                        @change="checkFile($event)"
                                        class="block w-full text-sm file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-[#b22222] file:px-4 file:py-2 file:font-semibold file:text-white hover:file:bg-[#8b1a1a]">
                                    <p class="mt-2 text-[13px] italic text-gray-400"><i class="fa-solid fa-circle-info"></i> PDF only • Max 5MB</p>
                                    <p class="mt-1 text-[13px] font-semibold text-red-600" x-text="fileErrors['{{ $name }}']"></p>
                                    <p class="mt-1 text-[13px] font-semibold text-red-600">{{ $errors->first($name) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ============ STEP 3: Interview Schedule ============ --}}
                <div x-show="step === 3" x-cloak x-transition.opacity.duration.300ms>
                    <div class="mb-8 border-b-2 border-gray-100 pb-5">
                        <h2 class="mb-2 text-xl font-extrabold text-[#b22222] sm:text-[28px]">Step 3: Interview Schedule</h2>
                        <p class="text-[15px] text-gray-500">Your scheduled interview and evaluation dates</p>
                    </div>

                    @if ($application && $application->interview_date)
                        <div class="rounded-[10px] border-l-[5px] border-[#b22222] bg-gradient-to-br from-[#fff9f9] to-[#f9f9f9] p-6 shadow-[0_4px_12px_rgba(0,0,0,0.05)]">
                            <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-[#b22222]">
                                <i class="fa-solid fa-calendar-check"></i> Interview
                            </h3>
                            <dl class="grid gap-3 text-[15px] sm:grid-cols-[150px_1fr]">
                                <dt class="font-semibold text-gray-800">Date</dt>
                                <dd class="text-gray-600">{{ $application->interview_date->format('F j, Y') }}</dd>

                                <dt class="font-semibold text-gray-800">Time</dt>
                                <dd class="text-gray-600">{{ $application->interview_time ?: 'To be announced' }}</dd>

                                <dt class="font-semibold text-gray-800">Location</dt>
                                <dd class="text-gray-600">{{ $application->interview_location ?: 'St. Mary Magdalene School' }}</dd>

                                @if ($application->interview_note)
                                    <dt class="font-semibold text-gray-800">Note</dt>
                                    <dd class="whitespace-pre-line break-words text-gray-600">{{ $application->interview_note }}</dd>
                                @endif
                            </dl>
                        </div>
                    @else
                        <p class="my-5 text-center text-base text-gray-500">No interview has been scheduled yet. Please check back soon.</p>
                    @endif
                </div>

                {{-- ============ STEP 4: Status ============ --}}
                <div x-show="step === 4" x-cloak x-transition.opacity.duration.300ms>
                    <div class="mb-8 border-b-2 border-gray-100 pb-5">
                        <h2 class="mb-2 text-xl font-extrabold text-[#b22222] sm:text-[28px]">Step 4: Admin and HR Approval Status</h2>
                        <p class="text-[15px] text-gray-500">Please wait for Admin and HR Confirmation</p>
                    </div>

<<<<<<< HEAD
                    @if (in_array($status, ['qualified', 'approved'], true))
=======
                    @if ($status === 'qualified')
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                        <div class="flex min-h-[300px] flex-col items-center justify-center rounded-xl border-2 border-[#28a745] bg-gradient-to-br from-[#d4edda] to-[#c3e6cb] p-10 text-center text-[#155724] shadow-[0_8px_25px_rgba(40,167,69,0.2)]">
                            <h3 class="mb-3 text-[28px] font-bold">🎉 Qualified</h3>
                            <p class="text-lg font-semibold">Congratulations! HR will contact you with the next steps.</p>
                        </div>
<<<<<<< HEAD
                    @elseif (in_array($status, ['hr_rejected', 'rejected'], true))
=======
                    @elseif ($status === 'rejected')
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                        <div class="flex min-h-[300px] flex-col items-center justify-center rounded-xl border-2 border-[#dc3545] bg-gradient-to-br from-[#f8d7da] to-[#f5c6cb] p-10 text-center text-[#721c24] shadow-[0_8px_25px_rgba(220,53,69,0.2)]">
                            <h3 class="mb-3 text-[28px] font-bold">Not Qualified</h3>
                            <p class="text-lg font-semibold">Thank you for applying. Unfortunately, your application was not successful.</p>
                            @if ($application->rejection_reason ?? null)
                                <p class="mt-1 max-w-[680px] font-medium">{{ $application->rejection_reason }}</p>
                            @endif
                        </div>
<<<<<<< HEAD
                    @elseif ($status === 'hr_approved')
                        <div class="flex min-h-[300px] flex-col items-center justify-center rounded-xl border-2 border-[#ffc107] bg-gradient-to-br from-[#fff3cd] to-[#ffeaa7] p-10 text-center text-[#856404] shadow-[0_8px_25px_rgba(255,193,7,0.2)]">
                            <h3 class="mb-3 text-[28px] font-bold">⏳ Awaiting Admin Approval</h3>
                            <p class="text-lg font-semibold">HR approved your application. It is now waiting for Admin's final approval.</p>
                        </div>
                    @else
                        <div class="flex min-h-[300px] flex-col items-center justify-center rounded-xl border-2 border-[#ffc107] bg-gradient-to-br from-[#fff3cd] to-[#ffeaa7] p-10 text-center text-[#856404] shadow-[0_8px_25px_rgba(255,193,7,0.2)]">
                            <h3 class="mb-3 text-[28px] font-bold">⏳ Awaiting HR and Admin Approval</h3>
                            <p class="text-lg font-semibold">Your interview is complete. HR will review your application first, followed by Admin.</p>
=======
                    @else
                        <div class="flex min-h-[300px] flex-col items-center justify-center rounded-xl border-2 border-[#ffc107] bg-gradient-to-br from-[#fff3cd] to-[#ffeaa7] p-10 text-center text-[#856404] shadow-[0_8px_25px_rgba(255,193,7,0.2)]">
                            <h3 class="mb-3 text-[28px] font-bold">⏳ Pending</h3>
                            <p class="text-lg font-semibold">Waiting for Admin and HR confirmation...</p>
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                        </div>
                    @endif
                </div>

                {{-- ============ Navigation buttons ============ --}}
                <div class="mt-12 flex flex-col-reverse items-stretch gap-3 border-t-2 border-gray-100 pt-8 sm:flex-row sm:items-center sm:justify-end">

                    {{-- Back (step 2 only, before submitting) --}}
                    <button type="button" x-show="step === 2 && !submitted" @click="back()"
                        class="min-w-[140px] cursor-pointer rounded-lg bg-gray-100 px-7 py-3.5 font-bold text-gray-800 transition hover:bg-gray-300">
                        Back
                    </button>

<<<<<<< HEAD
=======
                    {{-- After submitting: only move between steps 3 and 4 --}}
                    <button type="button" x-show="submitted && step === 4" @click="step = 3"
                        class="min-w-[140px] cursor-pointer rounded-lg bg-gray-100 px-7 py-3.5 font-bold text-gray-800 transition hover:bg-gray-300">
                        Back
                    </button>
                    <button type="button" x-show="submitted && step === 3" @click="step = 4"
                        class="min-w-[140px] cursor-pointer rounded-lg bg-[#b22222] px-7 py-3.5 font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#8b1a1a] hover:shadow-[0_6px_20px_rgba(178,34,34,0.4)]">
                        Next
                    </button>

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                    {{-- Step 1 -> 2 --}}
                    <button type="button" x-show="step === 1 && !submitted" @click="next()" :disabled="!hasOpenings"
                        class="min-w-[140px] cursor-pointer rounded-lg bg-[#b22222] px-7 py-3.5 font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#8b1a1a] hover:shadow-[0_6px_20px_rgba(178,34,34,0.4)] disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:shadow-none">
                        Next
                    </button>

                    {{-- Submit (step 2) --}}
                    <button type="button" x-show="step === 2 && !submitted" @click="submit()" :disabled="sending"
                        class="min-w-[140px] cursor-pointer rounded-lg bg-[#28a745] px-7 py-3.5 font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#218838] hover:shadow-[0_6px_20px_rgba(40,167,69,0.4)] disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="sending ? 'Submitting...' : 'Submit Application'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function applicationForm(startStep, submitted, hasOpenings) {
            return {
                step: startStep,
                submitted: submitted,
                hasOpenings: hasOpenings,
                sending: false,
                fileErrors: {},

                // Check every required field inside one step
                validateStep(ref) {
                    const fields = this.$refs[ref].querySelectorAll('input, select');
                    for (const el of fields) {
                        if (!el.checkValidity()) {
                            el.reportValidity();
                            return false;
                        }
                    }
                    return true;
                },

                next() {
                    if (!this.hasOpenings) return;
                    if (this.validateStep('step1')) this.step = 2;
                },

                back() {
                    if (this.step > 1) this.step--;
                },

                // PDF only, max 5MB
                checkFile(e) {
                    const input = e.target;
                    const file = input.files[0];
                    this.fileErrors[input.name] = '';
                    if (!file) return;

                    if (file.type !== 'application/pdf') {
                        this.fileErrors[input.name] = 'Only PDF files are allowed.';
                        input.value = '';
                    } else if (file.size > 5 * 1024 * 1024) {
                        this.fileErrors[input.name] = 'File is too large (max 5MB).';
                        input.value = '';
                    }
                },

                submit() {
                    if (!this.hasOpenings) { this.step = 1; return; }
                    if (!this.validateStep('step1')) { this.step = 1; return; }
                    if (!this.validateStep('step2')) return;
                    this.sending = true;
                    this.$refs.form.submit();
                },
            };
        }
    </script>
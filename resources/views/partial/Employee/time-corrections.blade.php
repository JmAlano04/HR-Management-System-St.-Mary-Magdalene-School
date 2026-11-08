{{-- resources/views/time-corrections/employee.blade.php (Employee: submit a correction request) --}}
      @auth
    <x-slot name="title">
        @if (auth()->user()->hasRole('Employee'))
            Employee Time Correction Request
       @endif
    </x-slot>
    @endauth

<x-slot name="header">
        <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-4xl font-extrabold leading-tight sm:ml-[10px] md:ml-[20px] lg:ml-[80px] text-red-700">Time Correction Request</h1>
    </x-slot>
    @php
<<<<<<< HEAD
        $wrapClass  = 'flex min-h-11 min-w-0 w-full items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-gray-500 transition focus-within:border-[#b22222] focus-within:ring-2 focus-within:ring-[#b22222]/10';
        $inputClass = 'min-w-0 w-full border-0 bg-transparent p-0 text-gray-900 outline-none focus:ring-0';
=======
        $wrapClass  = 'flex min-h-[50px] w-full items-center gap-2.5 rounded-xl border border-[#e8e0e0] bg-[#fffdfd] px-3.5 text-gray-500 transition focus-within:border-[#b22222] focus-within:ring-4 focus-within:ring-[#b22222]/10';
        $inputClass = 'w-full border-0 bg-transparent p-0 text-gray-900 outline-none focus:ring-0';
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $errClass   = 'text-xs font-semibold text-red-600';

        // Review badge look (HR / Admin)
        $badge = fn (string $status) => match ($status) {
            'Approved' => 'bg-[#e4f3e9] text-[#26734d]',
            'Rejected' => 'bg-[#fbe4e4] text-[#b22222]',
            default    => 'bg-[#f8eadc] text-[#9a5b1a]',
        };
    @endphp

<<<<<<< HEAD
    <div class="mx-auto max-w-[1920px] px-4 pb-12 pt-4 sm:px-8 lg:px-12"
        @keydown.escape.window="closePreview()"
        x-data="correctionRequest(@js([
            'type' => old('correction_type', ''),
            'date' => old('correction_date', ''),
            'incorrectTime' => old('incorrect_time', ''),
            'attendanceUrl' => route('time-corrections.attendance-punch'),
        ]))"
        x-init="loadIncorrectTime(true)">

  
        <section aria-labelledby="correctionTitle"
            class="mx-auto max-w-[1120px] rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
=======
    <div class="mx-auto max-w-[1920px] px-4 pb-12 pt-6 sm:px-8 lg:px-12"
        x-data="correctionRequest(@js(['type' => old('correction_type', '')]))">

  
        <section aria-labelledby="correctionTitle"
            class="mx-auto mt-7 max-w-[1120px] rounded-[28px] border border-[#f0e5e5] bg-white px-5 pb-7 shadow-[0_20px_50px_rgba(15,23,42,0.08)] sm:px-7">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

            @unless ($employee)
                <div class="mt-7 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    Your account is not linked to an employee record yet, so you cannot file a request. Please contact HR.
                </div>
            @endunless

<<<<<<< HEAD
            <form method="POST" action="{{ route('time-corrections.store') }}" class="grid gap-6">
=======
            <form method="POST" action="{{ route('time-corrections.store') }}" enctype="multipart/form-data"
                class="grid gap-[34px] pt-[30px]" @submit="validateBeforeSubmit($event)">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                @csrf

                {{-- ============ Correction details ============ --}}
                <div>
<<<<<<< HEAD
                    <div class="mb-4">
                        <h3 id="correctionTitle" class="mb-1 text-base font-bold text-gray-900">Correction details</h3>
                        <p class="text-sm text-gray-500">Match the request to the date shown in the logbook.</p>
                    </div>

                    <div class="grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,3fr)_max-content_minmax(0,2fr)_minmax(0,2fr)]">
                        <label class="grid min-w-0 gap-2 text-sm font-semibold text-gray-700">
                            <span>Date that needs correction <b class="text-[#b22222]">*</b></span>
                            <span class="{{ $wrapClass }}">
                                <i class="fa-regular fa-calendar"></i>
                                <input type="date" name="correction_date" x-model="date" @change="loadIncorrectTime()" max="{{ $today }}" required class="{{ $inputClass }}">
=======
                    <div class="mb-5">
                        <h3 id="correctionTitle" class="mb-1 mt-0.5 text-base font-bold text-[#231f20]">Correction details</h3>
                        <p class="text-[0.86rem] text-gray-500">Match the request to the date shown in the logbook.</p>
                    </div>

                    <div class="grid items-end gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="grid min-w-0 gap-[9px] text-[0.83rem] font-bold text-[#3f3737]">
                            <span>Date that needs correction <b class="text-[#b22222]">*</b></span>
                            <span class="{{ $wrapClass }}">
                                <i class="fa-regular fa-calendar"></i>
                                <input type="date" name="correction_date" value="{{ old('correction_date') }}" max="{{ $today }}" required class="{{ $inputClass }}">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                            </span>
                            <span class="{{ $errClass }}">{{ $errors->first('correction_date') }}</span>
                        </label>

<<<<<<< HEAD
                        <label class="grid min-w-0 gap-2 text-sm font-semibold text-gray-700 lg:w-fit">
                            <span>Time entry type <b class="text-[#b22222]">*</b></span>
                            <span class="{{ $wrapClass }} inline-flex max-w-full lg:!w-fit">
                                <i class="fa-regular fa-clock"></i>
                                <select name="correction_type" x-model="type" @change="loadIncorrectTime()" required class="{{ $inputClass }} min-w-0 cursor-pointer whitespace-nowrap appearance-none lg:!w-[220px] lg:flex-none">
=======
                        <label class="grid min-w-0 gap-[9px] text-[0.83rem] font-bold text-[#3f3737]">
                            <span>Time entry type <b class="text-[#b22222]">*</b></span>
                            <span class="{{ $wrapClass }}">
                                <i class="fa-regular fa-clock"></i>
                                <select name="correction_type" x-model="type" required class="{{ $inputClass }} cursor-pointer appearance-none">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                                    <option value="" disabled>Select time in or time out</option>
                                    <option value="Time In">Time In</option>
                                    <option value="Time Out">Time Out</option>
                                </select>
                            </span>
                            <span class="{{ $errClass }}">{{ $errors->first('correction_type') }}</span>
                        </label>

<<<<<<< HEAD
                        <label class="grid min-w-0 gap-2 text-sm font-semibold text-gray-700">
                            <span>Incorrect <span x-text="type ? type.toLowerCase() : ''"></span> <b class="text-[#b22222]">*</b></span>
                            <span class="{{ $wrapClass }}">
                                <i class="fa-regular fa-x" style="color: rgb(217, 6, 6);"></i>
                                <input type="time" name="incorrect_time" x-model="incorrectTime" required
=======
                        <label class="grid min-w-0 gap-[9px] text-[0.83rem] font-bold text-[#3f3737]">
                            <span>Incorrect <span x-text="type ? type.toLowerCase() : ''"></span> <b class="text-[#b22222]">*</b></span>
                            <span class="{{ $wrapClass }}">
                                <i class="fa-regular fa-x" style="color: rgb(217, 6, 6);"></i>
                                <input type="time" name="incorrect_time" value="{{ old('incorrect_time') }}" required
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                                    :min="type === 'Time In' ? '06:00' : null" :max="type === 'Time In' ? '16:30' : null" class="{{ $inputClass }}">
                            </span>
                            <span class="{{ $errClass }}">{{ $errors->first('incorrect_time') }}</span>
                        </label>

<<<<<<< HEAD
                        <label class="grid min-w-0 gap-2 text-sm font-semibold text-gray-700">
=======
                        <label class="grid min-w-0 gap-[9px] text-[0.83rem] font-bold text-[#3f3737]">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                            <span>Correct <span x-text="type ? type.toLowerCase() : ''"></span> <b class="text-[#b22222]">*</b></span>
                            <span class="{{ $wrapClass }}">
                                <i class="fa-solid fa-check" style="color: rgb(0, 142, 21);"></i>
                                <input type="time" name="correct_time" value="{{ old('correct_time') }}" required
                                    :min="type === 'Time In' ? '06:00' : null" :max="type === 'Time In' ? '16:30' : null" class="{{ $inputClass }}">
                            </span>
                            <span class="{{ $errClass }}">{{ $errors->first('correct_time') }}</span>
                        </label>

<<<<<<< HEAD
                        <label class="grid min-w-0 gap-2 text-sm font-semibold text-gray-700 sm:col-span-2 lg:col-span-4">
                            <span>What happened? <em class="text-xs font-medium not-italic text-gray-400">Optional</em></span>
                            <textarea name="note" rows="3" maxlength="1000"
                                placeholder="Add a short note about the logbook entry or missed punch..."
                                class="min-h-24 w-full resize-y rounded-lg border border-gray-300 bg-white p-3 leading-normal text-gray-900 outline-none focus:border-[#b22222] focus:ring-2 focus:ring-[#b22222]/10">{{ old('note') }}</textarea>
=======
                        <label class="grid min-w-0 gap-[9px] text-[0.83rem] font-bold text-[#3f3737] sm:col-span-2 lg:col-span-4">
                            <span>What happened? <em class="text-xs font-medium not-italic text-gray-400">Optional</em></span>
                            <textarea name="note" rows="3" maxlength="1000"
                                placeholder="Add a short note about the logbook entry or missed punch..."
                                class="min-h-[88px] w-full resize-y rounded-xl border border-[#e8e0e0] bg-transparent p-3.5 leading-normal text-gray-900 outline-none focus:border-[#b22222] focus:ring-4 focus:ring-[#b22222]/10">{{ old('note') }}</textarea>
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                            <span class="{{ $errClass }}">{{ $errors->first('note') }}</span>
                        </label>
                    </div>
                </div>

<<<<<<< HEAD
                {{-- ============ Review status ============ --}}
                <div class="border-t border-gray-200 pt-6">
                    {{-- Review status of the latest request --}}
                    @if ($latest)
                        <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3" aria-live="polite">
                            <div class="pb-2 text-gray-800">
                                <div class="grid gap-1">
                                    <strong class="text-sm">Review status</strong>
=======
                {{-- ============ Proof from the logbook ============ --}}
                <div class="border-t border-[#f2eeee] pt-[30px]">
                    <div class="mb-5">
                        <h3 class="mb-1 mt-0.5 text-base font-bold text-[#231f20]">Proof from the logbook</h3>
                        <p class="text-[0.86rem] text-gray-500">Upload a clear image showing the relevant logbook entry.</p>
                    </div>

                    <label for="proofImage" id="proofDropzone"
                        @dragenter.prevent="dragging = true" @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false" @drop.prevent="dragging = false; dropFile($event)"
                        class="relative flex min-h-[190px] cursor-pointer flex-col items-center justify-center rounded-2xl border-[1.5px] border-dashed p-6 text-center text-gray-500 transition duration-200"
                        :class="dragging ? '-translate-y-0.5 border-[#b22222] bg-[#fff4f4]' : 'border-[#d8bcbc] bg-[#fffafa] hover:-translate-y-0.5 hover:border-[#b22222] hover:bg-[#fff4f4]'">
                        <input id="proofImage" name="proof" type="file" x-ref="proof" required
                            accept="image/png,image/jpeg,image/webp" class="absolute h-px w-px opacity-0"
                            @change="showFile($event.target.files[0])">
                        <span class="mb-[11px] grid h-[42px] w-[42px] place-items-center rounded-[13px] bg-[#f6e2e2] text-[#b22222]"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                        <strong class="text-[0.95rem] text-[#342b2b]">Drop your image here</strong>
                        <span>or <u class="font-bold text-[#b22222] underline-offset-[3px]">browse from your device</u></span>
                        <small class="mt-2.5 text-xs text-gray-400">PNG, JPG, or WEBP up to 10 MB</small>
                    </label>
                    <p class="mt-2 text-xs font-semibold text-red-600" x-text="fileError"></p>
                    <p class="{{ $errClass }} mt-1">{{ $errors->first('proof') }}</p>

                    {{-- Selected file --}}
                    <div x-show="fileName" x-cloak class="mt-3.5 flex items-center gap-3.5 rounded-xl border border-[#eadede] bg-[#fffafa] px-3 py-2.5">
                        <div class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-[10px] bg-[#fef2f2] text-[1.1rem] text-[#b22222]" aria-hidden="true">
                            <i class="fa-regular fa-file-image"></i>
                        </div>
                        <div class="grid min-w-0 flex-1 gap-1">
                            <div class="flex items-center justify-between gap-3.5">
                                <strong class="truncate text-[0.86rem] text-[#342b2b]" x-text="fileName"></strong>
                                <span class="shrink-0 text-xs text-gray-500" x-text="fileSize"></span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-[#f1dddd]" role="progressbar" aria-label="Proof image upload progress"
                                aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progress">
                                <span class="block h-full rounded-full bg-[#b22222] transition-[width] duration-100 ease-linear" :style="'width:' + progress + '%'"></span>
                            </div>
                        </div>
                        <span class="w-[38px] shrink-0 text-right text-[0.78rem] font-extrabold text-[#b22222]" x-text="progress + '%'"></span>
                        <button type="button" @click="clearFile()" aria-label="Remove proof image"
                            class="grid h-[34px] w-[34px] shrink-0 cursor-pointer place-items-center rounded-[9px] bg-[#f9e8e8] text-[#b22222]">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    {{-- Review status of the latest request --}}
                    @if ($latest)
                        <div class="mt-3.5 rounded-xl border border-[#eadede] bg-[#fffafa] p-4" aria-live="polite">
                            <div class="flex items-center gap-[11px] pb-3 text-[#342b2b]">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-[9px] bg-[#fef2f2] text-[#b22222]"><i class="fa-solid fa-clipboard-check"></i></span>
                                <div class="grid gap-[3px]">
                                    <strong class="text-[0.86rem]">Review status</strong>
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                                    <span class="text-xs text-gray-500">
                                        Latest request: {{ $latest->correction_type }} on {{ $latest->correction_date->format('M j, Y') }}.
                                    </span>
                                </div>
                            </div>
                            <div class="grid gap-2">
<<<<<<< HEAD
                                <div class="flex items-center justify-between gap-4 border-t border-gray-200 px-2 py-2 text-sm text-gray-700">
=======
                                <div class="flex items-center justify-between gap-4 border-t border-[#f2e5e5] px-3 py-2.5 text-[0.8rem] text-[#4b4040]">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                                    <span class="inline-flex items-center gap-2"><i class="fa-solid fa-user-tie w-4 text-center text-[#b22222]"></i> HR review</span>
                                    <strong class="whitespace-nowrap rounded-full px-[9px] py-[5px] text-[0.7rem] {{ $badge($latest->hr_status) }}">
                                        {{ $latest->hr_status === 'Pending' ? 'Pending review' : $latest->hr_status }}
                                    </strong>
                                </div>
<<<<<<< HEAD
                                <div class="flex items-center justify-between gap-4 border-t border-gray-200 px-2 py-2 text-sm text-gray-700">
=======
                                <div class="flex items-center justify-between gap-4 border-t border-[#f2e5e5] px-3 py-2.5 text-[0.8rem] text-[#4b4040]">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                                    <span class="inline-flex items-center gap-2"><i class="fa-solid fa-user-shield w-4 text-center text-[#b22222]"></i> Admin review</span>
                                    <strong class="whitespace-nowrap rounded-full px-[9px] py-[5px] text-[0.7rem] {{ $badge($latest->admin_status) }}">
                                        {{ $latest->admin_status === 'Pending' ? 'Pending review' : $latest->admin_status }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ============ Actions ============ --}}
<<<<<<< HEAD
                <div class="flex flex-col items-stretch justify-between gap-4 border-t border-gray-200 pt-4 sm:flex-row sm:items-center">
                    <p class="min-h-5 text-sm font-semibold text-[#26734d]" role="status" aria-live="polite">{{ session('success') }}</p>

                    <button type="submit" @disabled(! $employee)
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-[#b22222] px-5 py-3 font-semibold text-white transition hover:bg-[#8f1c1c] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#b22222] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
=======
                <div class="flex flex-col items-stretch justify-between gap-5 pt-0.5 sm:flex-row sm:items-center">
                    <p class="min-h-5 text-[0.84rem] font-semibold text-[#26734d]" role="status" aria-live="polite">{{ session('success') }}</p>

                    <button type="submit" @disabled(! $employee)
                        class="inline-flex cursor-pointer items-center justify-center gap-3 rounded-[11px] bg-[#b22222] px-[19px] py-[13px] font-bold text-white shadow-[0_10px_20px_rgba(178,34,34,0.18)] transition hover:-translate-y-0.5 hover:bg-[#8f1c1c] disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0">
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                        Submit correction <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
        function correctionRequest(cfg) {
            return {
                type: cfg.type,
<<<<<<< HEAD
                date: cfg.date,
                incorrectTime: cfg.incorrectTime,
                attendanceUrl: cfg.attendanceUrl,
                punchRequest: 0,

                async loadIncorrectTime(initial = false) {
                    const requestId = ++this.punchRequest;
                    if (!this.date || !this.type) {
                        return;
                    }

                    if (!initial || !this.incorrectTime) this.incorrectTime = '';

                    try {
                        const url = new URL(this.attendanceUrl, window.location.origin);
                        url.searchParams.set('correction_date', this.date);
                        url.searchParams.set('correction_type', this.type);
                        const response = await fetch(url, {
                            headers: { Accept: 'application/json' },
                        });
                        if (!response.ok) throw new Error('Could not load the recorded attendance time.');

                        const result = await response.json();
                        if (requestId !== this.punchRequest) return;

                        if (result.incorrect_time) {
                            this.incorrectTime = result.incorrect_time;
                        }
                    } catch (error) {
                        if (requestId !== this.punchRequest) return;
                        console.error('Could not load the recorded attendance time.', error);
                    }
=======
                dragging: false,
                fileName: '',
                fileSize: '',
                fileError: '',
                progress: 0,
                timer: null,

                formatSize(bytes) {
                    return bytes < 1024 * 1024
                        ? Math.max(1, Math.round(bytes / 1024)) + ' KB'
                        : (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                },

                // Only images up to 10 MB are accepted (the server checks again)
                showFile(file) {
                    this.fileError = '';
                    if (this.timer) clearInterval(this.timer);

                    if (!file) { this.clearFile(); return; }

                    if (!file.type.startsWith('image/')) {
                        this.fileError = 'Please choose an image file for the logbook proof.';
                        this.clearFile(false);
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        this.fileError = 'That image is larger than 10 MB. Please choose a smaller file.';
                        this.clearFile(false);
                        return;
                    }

                    this.fileName = file.name;
                    this.fileSize = this.formatSize(file.size);
                    this.progress = 0;

                    // Visual progress bar (same animation as the original design)
                    this.timer = setInterval(() => {
                        this.progress = Math.min(this.progress + Math.floor(Math.random() * 9) + 4, 100);
                        if (this.progress === 100) clearInterval(this.timer);
                    }, 45);
                },

                dropFile(event) {
                    const file = event.dataTransfer.files[0];
                    if (!file) return;
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    this.$refs.proof.files = transfer.files;
                    this.showFile(file);
                },

                clearFile(resetError = true) {
                    if (this.timer) clearInterval(this.timer);
                    this.$refs.proof.value = '';
                    this.fileName = '';
                    this.fileSize = '';
                    this.progress = 0;
                    if (resetError) this.fileError = '';
                },

                validateBeforeSubmit(event) {
                    if (!this.$refs.proof.files.length) {
                        event.preventDefault();
                        this.fileError = 'Please upload the logbook proof image.';
                        return false;
                    }
                    return true;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                },
            };
        }
    </script>

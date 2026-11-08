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
        $wrapClass  = 'flex min-h-11 min-w-0 w-full items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-gray-500 transition focus-within:border-[#b22222] focus-within:ring-2 focus-within:ring-[#b22222]/10';
        $inputClass = 'min-w-0 w-full border-0 bg-transparent p-0 text-gray-900 outline-none focus:ring-0';
        $errClass   = 'text-xs font-semibold text-red-600';

        // Review badge look (HR / Admin)
        $badge = fn (string $status) => match ($status) {
            'Approved' => 'bg-[#e4f3e9] text-[#26734d]',
            'Rejected' => 'bg-[#fbe4e4] text-[#b22222]',
            default    => 'bg-[#f8eadc] text-[#9a5b1a]',
        };
    @endphp

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

            @unless ($employee)
                <div class="mt-7 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    Your account is not linked to an employee record yet, so you cannot file a request. Please contact HR.
                </div>
            @endunless

            <form method="POST" action="{{ route('time-corrections.store') }}" class="grid gap-6">
                @csrf

                {{-- ============ Correction details ============ --}}
                <div>
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
                            </span>
                            <span class="{{ $errClass }}">{{ $errors->first('correction_date') }}</span>
                        </label>

                        <label class="grid min-w-0 gap-2 text-sm font-semibold text-gray-700 lg:w-fit">
                            <span>Time entry type <b class="text-[#b22222]">*</b></span>
                            <span class="{{ $wrapClass }} inline-flex max-w-full lg:!w-fit">
                                <i class="fa-regular fa-clock"></i>
                                <select name="correction_type" x-model="type" @change="loadIncorrectTime()" required class="{{ $inputClass }} min-w-0 cursor-pointer whitespace-nowrap appearance-none lg:!w-[220px] lg:flex-none">
                                    <option value="" disabled>Select time in or time out</option>
                                    <option value="Time In">Time In</option>
                                    <option value="Time Out">Time Out</option>
                                </select>
                            </span>
                            <span class="{{ $errClass }}">{{ $errors->first('correction_type') }}</span>
                        </label>

                        <label class="grid min-w-0 gap-2 text-sm font-semibold text-gray-700">
                            <span>Incorrect <span x-text="type ? type.toLowerCase() : ''"></span> <b class="text-[#b22222]">*</b></span>
                            <span class="{{ $wrapClass }}">
                                <i class="fa-regular fa-x" style="color: rgb(217, 6, 6);"></i>
                                <input type="time" name="incorrect_time" x-model="incorrectTime" required
                                    :min="type === 'Time In' ? '06:00' : null" :max="type === 'Time In' ? '16:30' : null" class="{{ $inputClass }}">
                            </span>
                            <span class="{{ $errClass }}">{{ $errors->first('incorrect_time') }}</span>
                        </label>

                        <label class="grid min-w-0 gap-2 text-sm font-semibold text-gray-700">
                            <span>Correct <span x-text="type ? type.toLowerCase() : ''"></span> <b class="text-[#b22222]">*</b></span>
                            <span class="{{ $wrapClass }}">
                                <i class="fa-solid fa-check" style="color: rgb(0, 142, 21);"></i>
                                <input type="time" name="correct_time" value="{{ old('correct_time') }}" required
                                    :min="type === 'Time In' ? '06:00' : null" :max="type === 'Time In' ? '16:30' : null" class="{{ $inputClass }}">
                            </span>
                            <span class="{{ $errClass }}">{{ $errors->first('correct_time') }}</span>
                        </label>

                        <label class="grid min-w-0 gap-2 text-sm font-semibold text-gray-700 sm:col-span-2 lg:col-span-4">
                            <span>What happened? <em class="text-xs font-medium not-italic text-gray-400">Optional</em></span>
                            <textarea name="note" rows="3" maxlength="1000"
                                placeholder="Add a short note about the logbook entry or missed punch..."
                                class="min-h-24 w-full resize-y rounded-lg border border-gray-300 bg-white p-3 leading-normal text-gray-900 outline-none focus:border-[#b22222] focus:ring-2 focus:ring-[#b22222]/10">{{ old('note') }}</textarea>
                            <span class="{{ $errClass }}">{{ $errors->first('note') }}</span>
                        </label>
                    </div>
                </div>

                {{-- ============ Review status ============ --}}
                <div class="border-t border-gray-200 pt-6">
                    {{-- Review status of the latest request --}}
                    @if ($latest)
                        <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3" aria-live="polite">
                            <div class="pb-2 text-gray-800">
                                <div class="grid gap-1">
                                    <strong class="text-sm">Review status</strong>
                                    <span class="text-xs text-gray-500">
                                        Latest request: {{ $latest->correction_type }} on {{ $latest->correction_date->format('M j, Y') }}.
                                    </span>
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <div class="flex items-center justify-between gap-4 border-t border-gray-200 px-2 py-2 text-sm text-gray-700">
                                    <span class="inline-flex items-center gap-2"><i class="fa-solid fa-user-tie w-4 text-center text-[#b22222]"></i> HR review</span>
                                    <strong class="whitespace-nowrap rounded-full px-[9px] py-[5px] text-[0.7rem] {{ $badge($latest->hr_status) }}">
                                        {{ $latest->hr_status === 'Pending' ? 'Pending review' : $latest->hr_status }}
                                    </strong>
                                </div>
                                <div class="flex items-center justify-between gap-4 border-t border-gray-200 px-2 py-2 text-sm text-gray-700">
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
                <div class="flex flex-col items-stretch justify-between gap-4 border-t border-gray-200 pt-4 sm:flex-row sm:items-center">
                    <p class="min-h-5 text-sm font-semibold text-[#26734d]" role="status" aria-live="polite">{{ session('success') }}</p>

                    <button type="submit" @disabled(! $employee)
                        class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-[#b22222] px-5 py-3 font-semibold text-white transition hover:bg-[#8f1c1c] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#b22222] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
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
                },
            };
        }
    </script>

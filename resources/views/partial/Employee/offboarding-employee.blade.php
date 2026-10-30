

    <x-slot name="header">
        <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-4xl font-extrabold leading-tight sm:ml-[10px] md:ml-[20px] lg:ml-[80px] text-red-700">Offboarding Request</h1>
    </x-slot>

    @php
        $interviewLabels = ['DICES Exit Interview', 'Exit Interview with School Director'];

        $documents = $case ? $case->documents->keyBy('document_label') : collect();
        $closed    = $case && $case->status === 'Closed';
        $rejected  = $case && $case->status === 'Rejected';
        $hasCase   = (bool) $case;

        $interviewRows = collect($requiredLabels)->filter(fn ($l) => in_array($l, $interviewLabels, true));
        $documentRows  = collect($requiredLabels)->reject(fn ($l) => in_array($l, $interviewLabels, true));

        $badge = fn ($status) => match ($status) {
            'Approved', 'Done' => 'bg-[#e8f5ec] text-[#21683a]',
            'Rejected'         => 'bg-[#fdeaea] text-[#b22222]',
            'Under review'     => 'bg-[#fff5d6] text-[#8a5a00]',
            default            => 'bg-gray-100 text-gray-600',
        };
    @endphp

    <div class="px-4 pb-12 sm:px-6 lg:px-10"
         x-data="{ notes: null, files: {} }"
         @keydown.escape.window="notes = null">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-4 max-w-[1100px] rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 max-w-[1100px] rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mt-2 grid max-w-[1100px] items-start gap-6 lg:grid-cols-2">

            {{-- ============ Request panel ============ --}}
            <section class="rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Employee services</p>
                        <h2 class="text-2xl font-bold text-gray-900">Start your resignation clearance</h2>
                        <p class="mt-2 text-gray-500">Send your request to HR to receive your required clearance documents and next steps.</p>
                    </div>
                    <span class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-lg bg-[#fef2f2] text-[#b22222]" aria-hidden="true">
                        <i class="fa-solid fa-file-signature"></i>
                    </span>
                </div>

                @if (! $hasCase)
                    <form method="POST" action="{{ route('employee.offboarding.store') }}" class="grid gap-4">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-1.5 text-sm font-semibold text-gray-700">
                                Employee name
                                <input type="text" value="{{ $employee->name }}" disabled
                                    class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 font-normal text-gray-500">
                            </label>
                            <label class="grid gap-1.5 text-sm font-semibold text-gray-700">
                                Position
                                <input type="text" value="{{ $employee->position }}" disabled
                                    class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 font-normal text-gray-500">
                            </label>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-1.5 text-sm font-semibold text-gray-700">
                                Last working day
                                <input type="date" name="last_working_day" value="{{ old('last_working_day') }}"
                                    min="{{ now()->toDateString() }}" required
                                    class="rounded-md border border-gray-300 px-3 py-2.5 font-normal text-gray-900 focus:border-[#b22222] focus:outline-none focus:ring-1 focus:ring-[#b22222]">
                            </label>
                            <label class="grid gap-1.5 text-sm font-semibold text-gray-700">
                                Reason
                                <input type="text" value="Resignation" disabled
                                    class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 font-normal text-gray-500">
                            </label>
                        </div>

                        <label class="grid gap-1.5 text-sm font-semibold text-gray-700">
                            <span>Message to HR <span class="ml-1 text-xs font-normal text-gray-400">Optional</span></span>
                            <textarea name="employee_message" rows="4" maxlength="2000"
                                placeholder="Add any details HR should know about your resignation."
                                class="rounded-md border border-gray-300 px-3 py-2.5 font-normal text-gray-900 focus:border-[#b22222] focus:outline-none focus:ring-1 focus:ring-[#b22222]">{{ old('employee_message') }}</textarea>
                        </label>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-gray-500">Your request will be sent to HR for review.</p>
                            <button type="submit"
                                class="min-h-[38px] cursor-pointer rounded-md border border-[#b22222] bg-[#b22222] px-4 py-2 font-bold text-white transition hover:bg-white hover:text-[#b22222]">
                                <i class="fa-solid fa-paper-plane mr-1" aria-hidden="true"></i> Send request
                            </button>
                        </div>
                    </form>
                @else
                    <p role="status" class="rounded-lg border border-[#f0dada] bg-[#fef2f2] px-4 py-3 text-sm font-semibold text-[#b22222]">
                        @if ($rejected)
                            Your offboarding request was rejected. Offboarding requests can only be submitted once.
                        @elseif ($closed)
                            Your offboarding request is closed. Offboarding requests can only be submitted once.
                        @else
                            You have already submitted your one offboarding request. Use the tracker to follow its progress.
                        @endif
                    </p>
                @endif
            </section>

            {{-- ============ Tracking panel ============ --}}
            <section class="rounded-[22px] border border-[#f0dada] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:p-7">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <p class="mb-1.5 text-xs font-extrabold uppercase tracking-[0.08em] text-[#b22222]">Request tracking</p>
                        <h2 class="text-2xl font-bold text-gray-900">Your clearance</h2>
                        <p class="mt-2 text-gray-500">Your submitted request and HR instructions will appear here.</p>
                    </div>
                    <span class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-lg bg-[#fef2f2] text-[#b22222]" aria-hidden="true">
                        <i class="fa-solid fa-list-check"></i>
                    </span>
                </div>

                @if (! $hasCase || $closed)
                    <div class="grid place-items-center gap-2 rounded-[14px] border border-dashed border-gray-300 px-4 py-10 text-center text-gray-500">
                        <i class="fa-regular fa-folder-open text-2xl" aria-hidden="true"></i>
                        <span>{{ $closed ? 'Your closed offboarding request has been removed from the tracker.' : 'No request submitted yet.' }}</span>
                    </div>
                @else
                    <article class="grid gap-4 rounded-[14px] border border-[#ecebea] bg-gray-50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center rounded-md px-2.5 py-1.5 text-[0.82rem] font-bold {{ $rejected ? 'bg-[#fdeaea] text-[#b22222]' : 'bg-[#fff5d6] text-[#8a5a00]' }}">
                                {{ $case->status_label }}
                            </span>
                            <span class="text-sm text-gray-500">Last day: {{ $case->last_working_day->format('M d, Y') }}</span>
                        </div>

                        <div>
                            <h3 class="text-[1.05rem] font-bold text-gray-900">Resignation request</h3>
                            <p class="mt-1 text-sm leading-relaxed text-gray-500">{{ $case->employee_message ?: 'No additional message provided.' }}</p>
                        </div>

                        @if ($rejected)
                            <p class="text-sm font-semibold text-[#b22222]">This request was rejected.</p>
                        @elseif (! $requirementsSent)
                            <p class="rounded-md border border-dashed border-gray-300 bg-white px-3 py-3 text-sm text-gray-500">
                                HR has not sent your document requirements yet.
                            </p>
                        @else
                            {{-- Interview clearances (HR marks these; no upload) --}}
                            @if ($interviewRows->isNotEmpty())
                                <section>
                                    <div class="mb-2 flex items-center justify-between">
                                        <strong class="text-sm text-gray-900">Interview Clearances</strong>
                                        <span class="text-xs text-gray-500">{{ $interviewRows->count() }} {{ Str::plural('interview', $interviewRows->count()) }}</span>
                                    </div>
                                    <ul class="grid list-none gap-2 p-0">
                                        @foreach ($interviewRows as $label)
                                            @php $status = ($documents[$label]->review_status ?? null) === 'Approved' ? 'Done' : 'Pending'; @endphp
                                            <li class="flex items-center justify-between gap-3 rounded-md border border-gray-200 bg-white px-3 py-2.5">
                                                <span class="text-sm font-semibold text-gray-900">{{ $label }}</span>
                                                <span class="rounded-md px-2.5 py-1 text-[0.78rem] font-bold {{ $badge($status) }}">{{ $status }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </section>
                            @endif

                            {{-- Documents and clearances (upload) --}}
                            <form method="POST" action="{{ route('employee.offboarding.documents.store', $case) }}"
                                  enctype="multipart/form-data" class="grid gap-3">
                                @csrf

                                <div class="flex items-center justify-between">
                                    <strong class="text-sm text-gray-900">Documents and clearances</strong>
                                    <span class="text-xs text-gray-500">{{ $documentRows->count() }} required</span>
                                </div>

                                <ul class="grid list-none gap-2 p-0">
                                    @foreach ($documentRows as $label)
                                        @php
                                            $doc    = $documents[$label] ?? null;
                                            $status = $doc->review_status ?? 'Required';
                                            $locked = $status === 'Approved';
                                        @endphp
                                        <li class="grid gap-2 rounded-md border border-gray-200 bg-white px-3 py-2.5">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="grid min-w-0 gap-0.5">
                                                    <span class="text-sm font-semibold text-gray-900">{{ $label }}</span>
                                                    @if ($doc)
                                                        <span class="break-all text-xs text-gray-500">{{ $doc->file_name }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex shrink-0 items-center gap-2">
                                                    <span class="rounded-md px-2.5 py-1 text-[0.78rem] font-bold {{ $badge($status) }}">{{ $status }}</span>
                                                    @if ($status === 'Rejected' && ! empty($doc->rejection_reason))
                                                        <button type="button" @click="notes = @js($doc->rejection_reason)"
                                                            class="cursor-pointer text-xs font-bold text-[#b22222] underline">Notes</button>
                                                    @endif
                                                </div>
                                            </div>

                                            @unless ($locked)
                                                <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-[#b22222]">
                                                    <i class="fa-solid fa-arrow-up-from-bracket text-xs" aria-hidden="true"></i>
                                                    <span x-text="files[@js($label)]
                                                        ? 'Change file'
                                                        : @js($status === 'Rejected' ? 'Upload replacement' : ($doc ? 'Replace file' : 'Choose file'))"></span>
                                                    <input type="file" class="sr-only"
                                                        name="documents[{{ $label }}]"
                                                        accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                                                        aria-label="Upload {{ $label }}"
                                                        @change="files[@js($label)] = $event.target.files[0]?.name || null">
                                                    <span class="break-all text-xs font-normal text-gray-500"
                                                        x-show="files[@js($label)]" x-text="files[@js($label)]"></span>
                                                </label>
                                            @endunless
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-sm text-gray-500" role="status" aria-live="polite"
                                       x-text="Object.values(files).some(Boolean)
                                           ? 'Your selected files will be sent to HR, replacing any previous upload.'
                                           : 'Choose at least one required document before sending.'"></p>
                                    <button type="submit"
                                        :disabled="!Object.values(files).some(Boolean)"
                                        class="min-h-[38px] cursor-pointer rounded-md border border-[#b22222] bg-[#b22222] px-4 py-2 font-bold text-white transition hover:bg-white hover:text-[#b22222] disabled:cursor-not-allowed disabled:border-gray-300 disabled:bg-gray-100 disabled:text-gray-500 disabled:hover:bg-gray-100 disabled:hover:text-gray-500">
                                        <i class="fa-solid fa-paper-plane mr-1" aria-hidden="true"></i> Send documents to HR
                                    </button>
                                </div>
                            </form>
                        @endif
                    </article>
                @endif
            </section>
        </div>

        {{-- ============ Rejection notes modal ============ --}}
        <div x-show="notes !== null" x-cloak x-transition.opacity class="fixed inset-0 z-[60] grid place-items-center p-5">
            <div class="absolute inset-0 bg-[rgba(17,24,39,0.55)]" @click="notes = null"></div>

            <section role="dialog" aria-modal="true" aria-labelledby="rejectionNotesTitle"
                class="relative w-full max-w-[480px] rounded-[20px] bg-white p-[26px] shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 id="rejectionNotesTitle" class="text-[1.2rem] font-bold">Rejection notes</h2>
                    <button type="button" @click="notes = null" aria-label="Close rejection notes"
                        class="h-9 w-9 cursor-pointer rounded-full bg-gray-100 text-2xl leading-none text-gray-900 transition hover:bg-gray-200">&times;</button>
                </div>
                <p class="whitespace-pre-line leading-relaxed text-gray-700" x-text="notes"></p>
            </section>
        </div>
    </div>

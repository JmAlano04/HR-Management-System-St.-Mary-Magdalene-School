{{-- resources/views/documents/index.blade.php --}}

    {{-- Browser tab title --}}
    <x-slot name="title">
        Document Management
    </x-slot>


    @php
        $fieldClass = 'w-full resize-y rounded-xl border border-gray-200 bg-gray-50 px-[13px] py-[11px] text-gray-900 focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10';
    @endphp

    <div
        class="px-4 pb-12 sm:px-6 lg:px-10"
        x-data="documentsPage(@js([
            'boxes'     => $boxes,
            'reopenBox' => $reopenBox,
        ]))"
        @keydown.escape.window="closeAll()"
    >

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-5 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->has('document_id') || $errors->has('decision'))
            <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                {{ $errors->first('document_id') ?: $errors->first('decision') }}
            </div>
        @endif

        {{-- ============ Stats ============ --}}
        <section class="mb-6 grid gap-6 rounded-[28px] border border-[#ecebea] bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
            <div class="grid grid-cols-1 gap-[18px] sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['Total Documents', $stats['total'],    'All uploaded employee files'],
                    ['Approved',        $stats['approved'], 'Accepted documents'],
                    ['Rejected',        $stats['rejected'], 'Documents needing review'],
                    ['Pending',         $stats['pending'],  'Awaiting approval'],
                ] as [$label, $count, $note])
                    <article class="rounded-[18px] border border-[#f3e0e0] bg-white px-6 py-[22px] shadow-[0_8px_20px_rgba(15,23,42,0.06)]">
                        <p class="mb-2 text-[0.85rem] uppercase tracking-[0.08em] text-gray-900"><strong>{{ $label }}</strong></p>
                        <h2 class="text-[2.3rem] font-extrabold leading-tight text-gray-900">{{ $count }}</h2>
                        <p class="mt-2.5 text-[0.95rem] text-gray-900">{{ $note }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- ============ Submission boxes ============ --}}
        <section aria-labelledby="submissionBoxTitle"
            class="mb-6 grid gap-[18px] rounded-[28px] border border-[#ecebea] bg-white p-6 shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
            <div>
                <h2 id="submissionBoxTitle" class="text-[1.35rem] font-bold text-gray-900">Create submission box</h2>
                <p class="mt-1.5 text-gray-500">Request a document from employees and make it available on their Documents page.</p>
            </div>

            <form method="POST" action="{{ route('boxes.store') }}"
                class="grid items-end gap-3.5 lg:grid-cols-[1.2fr_1fr_0.8fr_auto]">
                @csrf

                <div class="grid gap-[7px]">
                    <label for="submissionBoxName" class="text-[0.88rem] font-bold text-gray-700">Submission box name</label>
                    <input id="submissionBoxName" name="name" type="text" value="{{ old('name') }}" required
                        placeholder="e.g., 2026 NBI Clearance" class="{{ $fieldClass }}">
                    <p class="text-xs font-semibold text-red-600">{{ $errors->first('name') }}</p>
                </div>

                <div class="grid gap-[7px]">
                    <label for="submissionBoxType" class="text-[0.88rem] font-bold text-gray-700">Document type</label>
                    <input id="submissionBoxType" name="type" type="text" value="{{ old('type') }}" required
                        placeholder="e.g., NBI" class="{{ $fieldClass }}">
                    <p class="text-xs font-semibold text-red-600">{{ $errors->first('type') }}</p>
                </div>

                <div class="grid gap-[7px]">
                    <label for="submissionBoxDeadline" class="text-[0.88rem] font-bold text-gray-700">Deadline</label>
                    <input id="submissionBoxDeadline" name="deadline" type="date" value="{{ old('deadline') }}"
                        min="{{ now()->toDateString() }}" class="{{ $fieldClass }}">
                    <p class="text-xs font-semibold text-red-600">{{ $errors->first('deadline') }}</p>
                </div>

                <div class="grid gap-[7px] lg:col-span-3">
                    <label for="submissionBoxInstructions" class="text-[0.88rem] font-bold text-gray-700">Instructions</label>
                    <textarea id="submissionBoxInstructions" name="instructions" rows="2" maxlength="1000"
                        placeholder="Tell employees what to submit." class="{{ $fieldClass }}">{{ old('instructions') }}</textarea>
                    <p class="text-xs font-semibold text-red-600">{{ $errors->first('instructions') }}</p>
                </div>

                <button type="submit"
                    class="min-h-[45px] cursor-pointer rounded-xl bg-[#b22222] px-4 font-bold text-white transition hover:bg-[#dc2626]">
                    <i class="fa-solid fa-plus"></i> Create submission box
                </button>
            </form>

            {{-- Existing boxes --}}
            <div class="grid gap-2.5" aria-live="polite">
                <template x-for="b in boxes" :key="b.id">
                    <article tabindex="0" role="button" @click="openBox(b.id)" @keydown.enter.prevent="openBox(b.id)" @keydown.space.prevent="openBox(b.id)"
                        class="flex cursor-pointer items-center justify-between gap-4 rounded-[14px] border border-[#f3e0e0] bg-[#fffafa] px-4 py-3.5 transition duration-200 hover:-translate-y-px hover:border-[#dc2626] hover:shadow-[0_8px_18px_rgba(178,34,34,0.1)] focus-visible:-translate-y-px focus-visible:border-[#dc2626] focus-visible:outline-none">
                        <div class="min-w-0">
                            <strong class="block text-gray-900" x-text="b.name"></strong>
                            <span class="mt-1 block text-[0.9rem] text-gray-500"
                                x-text="b.type + (b.deadline ? ' · Due ' + b.deadline : ' · No deadline')"></span>
                            <p class="mt-1 text-[0.9rem] text-gray-500" x-show="b.instructions" x-text="b.instructions"></p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1.5 text-[0.78rem] font-bold"
                            :class="b.active ? 'bg-[#dcfce7] text-[#166534]' : 'bg-gray-100 text-gray-600'"
                            x-text="b.active ? 'Active' : 'Closed'"></span>
                    </article>
                </template>

                <p x-show="boxes.length === 0" class="mt-1 text-[0.9rem] text-gray-500">No submission boxes created yet.</p>
            </div>
        </section>

        {{-- ============ Submission box modal (employees / files) ============ --}}
        <div x-show="box && !doc" x-cloak x-transition.opacity class="fixed inset-0 z-[60] grid place-items-center p-4">
            <div class="fixed inset-0 bg-black/45" @click="closeAll()"></div>

            <div role="dialog" aria-modal="true" aria-labelledby="submissionBoxModalTitle"
                class="relative max-h-[min(700px,88vh)] w-[min(720px,92vw)] overflow-y-auto rounded-2xl border border-[#ecebea] bg-white px-[22px] py-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">

                <button type="button" x-show="employee" @click="backFromBox()" aria-label="Back to employees"
                    class="absolute left-5 top-[18px] grid h-[34px] w-[34px] cursor-pointer place-items-center rounded-full text-base text-gray-900 transition hover:-translate-x-0.5 hover:bg-[#fef2f2] hover:text-[#b22222]">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <button type="button" @click="closeAll()" aria-label="Close"
                    class="absolute right-3 top-2.5 cursor-pointer text-2xl text-gray-700 transition hover:scale-110 hover:text-[#b22222]">&times;</button>

                <header :class="employee ? 'pl-12' : ''">
                    <h2 id="submissionBoxModalTitle" class="mb-3 text-2xl font-bold text-[#b22222]"
                        x-text="employee ? employee.name : (box ? box.name : '')"></h2>
                    <p class="mb-3 text-gray-500"
                        x-text="employee ? (employee.number + ' · ' + box.name) : (box ? box.type + (box.deadline ? ' · Due ' + box.deadline : '') : '')"></p>
                </header>

                {{-- Employees who uploaded --}}
                <div class="grid gap-2.5" x-show="box && !employee">
                    <template x-for="e in (box ? box.employees : [])" :key="e.id">
                        <article tabindex="0" role="button" @click="openEmployee(e)" @keydown.enter.prevent="openEmployee(e)" @keydown.space.prevent="openEmployee(e)"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#f3e0e0] bg-[#fffafa] p-3 transition hover:border-[#dc2626] hover:shadow-[0_6px_14px_rgba(178,34,34,0.1)] focus-visible:border-[#dc2626] focus-visible:outline-none">
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#b22222] font-extrabold text-white" x-text="e.initials"></div>
                            <div>
                                <strong class="block text-gray-900" x-text="e.name"></strong>
                                <span class="mt-[3px] block text-[0.88rem] text-gray-500" x-text="e.number"></span>
                                <small class="mt-[3px] block text-[0.88rem] text-gray-500"
                                    x-text="e.documents.length + ' ' + (e.documents.length === 1 ? 'file' : 'files') + ' submitted'"></small>
                            </div>
                        </article>
                    </template>

                    <p x-show="box && box.employees.length === 0" class="mt-[3px] text-[0.88rem] text-gray-500">
                        No employees have uploaded a document to this box yet.
                    </p>
                </div>

                {{-- One employee's files --}}
                <div class="grid gap-2.5" x-show="employee">
                    <template x-for="(d, i) in (employee ? employee.documents : [])" :key="d.id">
                        <article tabindex="0" role="button" @click="openFile(d)" @keydown.enter.prevent="openFile(d)" @keydown.space.prevent="openFile(d)"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#f3e0e0] bg-[#fffafa] p-3 transition hover:border-[#dc2626] hover:shadow-[0_6px_14px_rgba(178,34,34,0.1)] focus-visible:border-[#dc2626] focus-visible:outline-none">
                            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#b22222] font-extrabold text-white">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>
                            <div class="min-w-0">
                                <strong class="block break-words text-gray-900" x-text="d.name || ('File ' + (i + 1))"></strong>
                                <span class="mt-[3px] block text-[0.88rem] text-gray-500" x-text="d.type + ' · ' + d.size"></span>
                                <small class="mt-[3px] block text-[0.88rem] text-gray-500" x-text="'Uploaded ' + d.uploaded + ' · ' + d.status"></small>
                            </div>
                        </article>
                    </template>
                </div>
            </div>
        </div>

        {{-- ============ Document detail modal ============ --}}
        <div x-show="doc" x-cloak x-transition.opacity class="fixed inset-0 z-[60] grid place-items-center p-4">
            <div class="fixed inset-0 bg-black/45" @click="closeAll()"></div>

            <div role="dialog" aria-modal="true" aria-labelledby="modalTitle"
                class="relative max-h-[92vh] w-[min(720px,92vw)] overflow-y-auto rounded-2xl border border-[#ecebea] bg-white px-[22px] py-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">

                <button type="button" @click="backFromDoc()" aria-label="Back to submitted files"
                    class="absolute left-5 top-[18px] grid h-[34px] w-[34px] cursor-pointer place-items-center rounded-full text-base text-gray-900 transition hover:-translate-x-0.5 hover:bg-[#fef2f2] hover:text-[#b22222]">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <button type="button" @click="closeAll()" aria-label="Close"
                    class="absolute right-3 top-2.5 cursor-pointer text-2xl text-gray-700 transition hover:scale-110 hover:text-[#b22222]">&times;</button>

                <template x-if="doc">
                    <div>
                        <header class="pl-12">
                            <h2 id="modalTitle" class="mb-3 text-2xl font-bold text-[#b22222]">Document Details</h2>
                        </header>

                        <div class="mb-3.5 grid gap-3">
                            <div class="flex items-center gap-3"><strong class="w-[140px] shrink-0 font-bold text-gray-500">Employee:</strong><span x-text="doc.employee_name"></span></div>
                            <div class="flex items-center gap-3"><strong class="w-[140px] shrink-0 font-bold text-gray-500">Employee ID:</strong><span x-text="doc.employee_number"></span></div>
                            <div class="flex items-center gap-3"><strong class="w-[140px] shrink-0 font-bold text-gray-500">Document Type:</strong><span x-text="doc.type"></span></div>
                            <div class="flex items-center gap-3">
                                <strong class="w-[140px] shrink-0 font-bold text-gray-500">File:</strong>
                                <a x-show="doc.file_url" :href="doc.file_url" target="_blank" rel="noopener"
                                    class="inline-flex min-w-0 items-center gap-2.5 rounded-[10px] border border-[#dc2626] bg-[#fef2f2] px-4 py-3 font-bold text-[#dc2626] transition hover:-translate-y-px hover:bg-[#b22222] hover:text-white">
                                    <i class="fa-solid fa-file-lines"></i>
                                    <span class="break-all" x-text="doc.name"></span>
                                </a>
                                <span x-show="!doc.file_url" class="text-gray-500">
                                    <span x-text="doc.name"></span> (preview unavailable)
                                </span>
                            </div>
                            <div class="flex items-center gap-3"><strong class="w-[140px] shrink-0 font-bold text-gray-500">Uploaded On:</strong><span x-text="doc.uploaded"></span></div>
                            <div class="flex items-center gap-3"><strong class="w-[140px] shrink-0 font-bold text-gray-500">Size:</strong><span x-text="doc.size"></span></div>
                            <div class="flex items-center gap-3">
                                <strong class="w-[140px] shrink-0 font-bold text-gray-500">Status:</strong>
                                <span class="inline-flex min-w-[92px] items-center justify-center rounded-full px-3.5 py-2 font-bold capitalize"
                                    :class="{
                                        'bg-[#ffedd5] text-[#92400e]': doc.status === 'Pending',
                                        'bg-[#dcfce7] text-[#166534]': doc.status === 'Approved',
                                        'bg-[#fee2e2] text-[#991b1b]': doc.status === 'Rejected',
                                    }"
                                    x-text="doc.status"></span>
                            </div>
                        </div>

                        {{-- Approve / Reject / Delete --}}
                        <footer class="flex flex-wrap justify-end gap-2.5">
                            <form method="POST" action="{{ route('hr.documents.decide') }}" class="flex flex-wrap gap-2.5">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="document_id" :value="docId">
                                <button type="submit" name="decision" value="Approved"
                                    class="cursor-pointer rounded-[28px] bg-[#16a34a] px-[22px] py-3 font-extrabold text-white transition hover:-translate-y-0.5 hover:brightness-95 hover:shadow-[0_8px_16px_rgba(15,23,42,0.14)]">Approve</button>
                                <button type="submit" name="decision" value="Rejected"
                                    class="cursor-pointer rounded-[28px] bg-[#ef4444] px-[22px] py-3 font-extrabold text-white transition hover:-translate-y-0.5 hover:brightness-95 hover:shadow-[0_8px_16px_rgba(15,23,42,0.14)]">Reject</button>
                            </form>

                            <form method="POST" action="{{ route('destroy') }}" @submit="confirmDelete($event)">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="document_id" :value="docId">
                                <button type="submit"
                                    class="cursor-pointer rounded-[28px] bg-[#7f1d1d] px-[22px] py-3 font-extrabold text-white transition hover:-translate-y-0.5 hover:brightness-95 hover:shadow-[0_8px_16px_rgba(15,23,42,0.14)]">Delete</button>
                            </form>
                        </footer>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function documentsPage(cfg) {
            return {
                boxes: cfg.boxes,
                boxId: null,        // submission box modal
                employeeId: null,   // one employee's files (inside the box modal)
                docId: null,        // document detail modal

                get box() {
                    return this.boxes.find((b) => b.id === this.boxId) || null;
                },

                get employee() {
                    return this.box ? (this.box.employees.find((e) => e.id === this.employeeId) || null) : null;
                },

                get doc() {
                    if (!this.box || !this.docId) return null;
                    for (const e of this.box.employees) {
                        const d = e.documents.find((x) => x.id === this.docId);
                        if (d) return d;
                    }
                    return null;
                },

                init() {
                    // After deleting a document the page reloads: reopen the box the file belonged to
                    if (cfg.reopenBox) this.openBox(cfg.reopenBox);
                },

                openBox(id) {
                    this.boxId = id;
                    this.employeeId = null;
                    this.docId = null;
                },

                // One file opens its details directly; several files open the employee's file list
                openEmployee(e) {
                    if (e.documents.length === 1) {
                        this.docId = e.documents[0].id;
                    } else {
                        this.employeeId = e.id;
                    }
                },

                openFile(d) { this.docId = d.id; },
                backFromBox() { this.employeeId = null; },
                backFromDoc() { this.docId = null; },

                closeAll() {
                    this.boxId = null;
                    this.employeeId = null;
                    this.docId = null;
                },

                confirmDelete(event) {
                    if (!confirm('Delete this document? This cannot be undone.')) event.preventDefault();
                },
            };
        }
    </script>
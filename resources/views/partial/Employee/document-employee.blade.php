{{-- resources/views/employee/documents/index.blade.php --}}
@php
    // Shape the data for Alpine (same idea as $months in the payslip view).
    $boxData = $boxes->map(fn ($box) => [
        'id'            => $box->id,
        'name'          => $box->name,
        'type'          => $box->type,
        'instructions'  => $box->instructions,
        'deadline'      => $box->deadline?->format('M d, Y'),
        'can_upload'    => $box->can_upload,
        'has_submitted' => $box->has_submitted,
        'documents'     => $box->documents->map(fn ($doc) => [
            'id'             => $doc->id,
            'name'           => $doc->name,
            'type'           => $doc->type ?: $box->type,
            'size'           => $doc->size,
            'uploaded_at'    => $doc->uploaded_at,
            'status'         => $doc->status,
            'status_message' => $doc->status_message,
            'file_url'       => route('employee.documents.file', $doc),
        ])->values(),
    ])->values();
@endphp

    <x-slot name="header">
        <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight ml-[80px] text-red-700">Documents</h1>
    </x-slot>

    <div
        class="mx-auto max-w-[1920px] px-4 pb-12 pt-6 sm:px-8 lg:px-12"
        x-data="employeeDocuments(@js([
            'boxes'         => $boxData,
            'reopenBoxId'   => (int) old('submission_box_id'),
        ]))"
        @keydown.escape.window="statusId ? statusId = null : (uploadBoxId ? closeUpload() : boxId = null)"
    >

        <section aria-labelledby="employeeDocumentsTitle"
            class="w-full rounded-[22px] border border-[#ecebea] bg-white p-[26px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">

            {{-- Heading + count --}}
            <div class="mb-[22px] flex items-center justify-between gap-4">
                <div>
                    <p class="mb-2 text-[0.82rem] font-bold text-gray-500">Requested by HR</p>
                    <h2 id="employeeDocumentsTitle" class="text-[1.45rem] font-bold text-gray-900">Document submissions</h2>
                    <p class="mt-1 text-sm text-gray-500">Upload the documents requested by HR.</p>
                </div>
                <span class="text-[0.82rem] font-bold text-gray-500">
                    {{ $boxes->count() ? $boxes->count() . ' submission box' . ($boxes->count() === 1 ? '' : 'es') : 'No submission boxes' }}
                </span>
            </div>

            {{-- Messages --}}
            @unless ($employee)
                <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    Your account is not linked to an employee record yet, so documents cannot be submitted. Please contact HR.
                </div>
            @endunless
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-3">

                @if ($boxes->isEmpty())
                    <div class="rounded-2xl border border-dashed border-[#d9dadd] px-5 py-[46px] text-center text-gray-500">
                        No submission boxes are currently available.
                    </div>
                @endif

                {{-- Submission box cards --}}
                <template x-for="b in boxes" :key="b.id">
                    <article tabindex="0" role="button"
                        @click="boxId = b.id"
                        @keydown.enter.prevent="boxId = b.id"
                        @keydown.space.prevent="boxId = b.id"
                        class="group grid cursor-pointer grid-cols-[42px_minmax(0,1fr)_auto] items-center gap-3.5 rounded-2xl border border-[#f1caca] bg-[#fffafa] px-[18px] py-4 transition hover:border-[#dc2626] hover:shadow-[0_8px_18px_rgba(178,34,34,0.1)] focus-visible:border-[#dc2626] focus-visible:outline-none">
                        <span class="grid h-[42px] w-[42px] place-items-center rounded-xl bg-[#fef2f2] text-[1.1rem] text-[#b22222]">
                            <i class="fa-solid fa-folder" aria-hidden="true"></i>
                        </span>

                        <span class="grid min-w-0 gap-1">
                            <strong class="text-gray-900" x-text="b.name"></strong>
                            <small class="text-[0.82rem] text-gray-500" x-text="b.type"></small>
                            <small x-show="b.instructions" class="text-[0.82rem] text-gray-500" x-text="b.instructions"></small>
                            <small x-show="b.deadline" class="text-[0.78rem] font-bold text-amber-700" x-text="'Deadline: ' + b.deadline"></small>
                        </span>

                        <span class="flex items-center gap-3">
                            <span x-show="b.documents.length"
                                class="rounded-full px-2.5 py-1 text-[0.7rem] font-extrabold"
                                :class="badge(b.documents[0].status)"
                                x-text="b.documents[0]?.status"></span>

                            <button type="button" x-show="b.can_upload" @click.stop="openUpload(b.id)"
                                class="min-h-[40px] cursor-pointer rounded-[10px] bg-[#b22222] px-3.5 font-bold text-white transition hover:bg-[#d93c3c]">
                                <i class="fa-solid fa-upload" aria-hidden="true"></i>
                                <span x-text="b.has_submitted ? 'Submit another' : 'Upload document'"></span>
                            </button>
                        </span>
                    </article>
                </template>
            </div>
        </section>

        {{-- ============ Submitted documents modal (one box) ============ --}}
        <div x-show="box" x-cloak x-transition.opacity @click.self="boxId = null"
            class="fixed inset-0 z-[60] grid place-items-center bg-[rgba(17,24,39,0.48)] p-5">
            <section role="dialog" aria-modal="true" aria-labelledby="employeeSubmissionTitle"
                class="relative max-h-[86vh] w-[min(760px,calc(100vw-40px))] overflow-y-auto rounded-[22px] border border-gray-200 bg-white p-7 shadow-[0_20px_50px_rgba(15,23,42,0.12)]">

                <button type="button" @click="boxId = null" aria-label="Close"
                    class="absolute right-3 top-2.5 grid h-[34px] w-[34px] cursor-pointer place-items-center rounded-lg text-gray-400 transition hover:bg-[#fef2f2] hover:text-[#b22222]">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

                <template x-if="box">
                    <div>
                        <header>
                            <h2 id="employeeSubmissionTitle" class="text-[1.8rem] font-bold text-[#b22222]" x-text="box.name"></h2>
                            <p class="mt-2 text-gray-500" x-text="box.type"></p>
                        </header>

                        <div class="mt-5 grid gap-3">
                            <template x-if="!box.documents.length">
                                <p class="mt-4 text-center text-gray-500">No documents submitted for this box yet.</p>
                            </template>

                            <template x-for="d in box.documents" :key="d.id">
                                <article tabindex="0" role="button" @click="statusId = d.id"
                                    @keydown.enter.prevent="statusId = d.id" @keydown.space.prevent="statusId = d.id"
                                    class="grid cursor-pointer grid-cols-[42px_minmax(0,1fr)_auto] items-center gap-3 rounded-2xl border border-[#f1caca] bg-[#fffafa] p-3.5 transition hover:border-[#dc2626] focus-visible:outline-none max-[600px]:grid-cols-[42px_minmax(0,1fr)]">
                                    <span class="grid h-[42px] w-[42px] place-items-center rounded-full bg-[#b22222] text-white">
                                        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                                    </span>
                                    <span class="grid min-w-0 gap-1">
                                        <strong class="truncate text-gray-900" x-text="d.name"></strong>
                                        <small class="text-[0.88rem] text-gray-500"
                                            x-text="d.type + ' · ' + d.size + ' · Uploaded ' + d.uploaded_at"></small>
                                    </span>
                                    <span class="rounded-full px-2.5 py-1 text-[0.7rem] font-extrabold max-[600px]:col-start-2 max-[600px]:justify-self-start"
                                        :class="badge(d.status)" x-text="d.status"></span>
                                </article>
                            </template>
                        </div>
                    </div>
                </template>
            </section>
        </div>

        {{-- ============ Document status modal ============ --}}
        <div x-show="doc" x-cloak x-transition.opacity @click.self="statusId = null"
            class="fixed inset-0 z-[70] grid place-items-center bg-[rgba(17,24,39,0.58)] p-5">
            <section role="dialog" aria-modal="true" aria-labelledby="employeeDocumentStatusTitle"
                class="relative w-[min(560px,calc(100vw-32px))] rounded-[22px] bg-white p-7 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">

                <button type="button" @click="statusId = null" aria-label="Close status"
                    class="absolute right-3 top-2.5 grid h-[34px] w-[34px] cursor-pointer place-items-center rounded-lg text-gray-400 transition hover:bg-[#fef2f2] hover:text-[#b22222]">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>

                <template x-if="doc">
                    <div>
                        <h2 id="employeeDocumentStatusTitle" class="mb-3 text-[1.35rem] font-bold text-gray-900">Document status</h2>

                        {{-- file_url is a signed-in, owner-only route (opens inline in a new tab) --}}
                        <a :href="doc.file_url" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center rounded-[10px] border border-[#dc2626] bg-[#fef2f2] px-4 py-3 font-bold text-[#dc2626] transition hover:-translate-y-px hover:bg-[#b22222] hover:text-white"
                            x-text="doc.name"></a>

                        <div class="mt-6 grid justify-items-start gap-4">
                            <span class="rounded-full px-2.5 py-1 text-[0.8rem] font-extrabold" :class="badge(doc.status)" x-text="doc.status"></span>
                            <p class="text-gray-500" x-text="doc.status_message"></p>
                        </div>
                    </div>
                </template>
            </section>
        </div>

        {{-- ============ Upload modal ============ --}}
        <div x-show="uploadBox" x-cloak x-transition.opacity @click.self="closeUpload()"
            class="fixed inset-0 z-[80] grid place-items-center bg-[rgba(17,24,39,0.48)] p-5">
            <section role="dialog" aria-modal="true" aria-labelledby="uploadDocumentTitle"
                class="relative w-[min(720px,calc(100vw-32px))] rounded-[22px] border border-gray-200 bg-white px-7 pb-6 pt-5 shadow-[0_26px_60px_rgba(15,23,42,0.12)]">

                <div class="flex items-center justify-between gap-4 border-b border-[#ddd] pb-4">
                    <h3 id="uploadDocumentTitle" class="text-[1.75rem] font-bold tracking-tight text-gray-900">Upload Document</h3>
                    <button type="button" @click="closeUpload()" aria-label="Close upload dialog"
                        class="grid h-[34px] w-[34px] cursor-pointer place-items-center rounded-lg text-gray-400 transition hover:bg-[#fef2f2] hover:text-[#b22222]">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('employee.documents.store') }}" enctype="multipart/form-data"
                    class="pt-6" @submit="validate($event)">
                    @csrf
                    <input type="hidden" name="submission_box_id" :value="uploadBoxId">

                    <div class="mb-6 grid gap-2.5">
                        <label for="documentTypeInput" class="font-semibold text-gray-900">Document Type</label>
                        <input id="documentTypeInput" type="text" name="type" x-model="docType" maxlength="255"
                            placeholder="e.g., Certificate, ID, Contract"
                            class="min-h-[52px] w-full rounded-xl border border-[#d4d4d4] px-4 text-gray-900 placeholder:text-gray-400 focus:border-[#b22222] focus:outline-none focus:ring-[3px] focus:ring-[#b22222]/15">
                    </div>

                    <div class="mb-3 grid gap-2.5">
                        <label class="font-semibold text-gray-900">Select File</label>
                        <div class="flex min-h-[52px] items-center gap-4 rounded-xl border border-[#d4d4d4] px-3.5">
                            <button type="button" @click="$refs.file.click()"
                                class="min-h-[40px] cursor-pointer rounded-[10px] border border-gray-300 bg-white px-4 font-bold text-gray-900 transition hover:bg-gray-50">Choose File</button>
                            <span class="truncate text-gray-500" x-text="fileName || 'No file chosen'"></span>
                            <input x-ref="file" type="file" name="file" hidden
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                @change="fileName = $refs.file.files[0]?.name || ''">
                        </div>
                    </div>

                    <p class="mb-2 text-sm text-gray-500">Max 10 MB. Accepted: PDF, DOCX, XLSX, JPEG, PNG</p>
                    <p x-show="clientError" x-text="clientError" class="mb-2 text-sm font-semibold text-red-700"></p>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="closeUpload()"
                            class="min-h-[44px] cursor-pointer rounded-xl px-6 font-bold text-gray-900 transition hover:bg-gray-100">Cancel</button>
                        <button type="submit"
                            class="min-h-[44px] cursor-pointer rounded-xl bg-[#b22222] px-8 font-bold text-white shadow-[0_8px_16px_rgba(178,34,34,0.18)] transition hover:bg-[#d93c3c]">Upload</button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        function employeeDocuments(cfg) {
            return {
                boxes: cfg.boxes,
                boxId: null,        // box whose submitted documents are shown
                statusId: null,     // document whose status is shown
                uploadBoxId: null,  // box the upload modal is for
                docType: '',
                fileName: '',
                clientError: '',

                get box() {
                    return this.boxes.find((b) => b.id === this.boxId) || null;
                },

                get uploadBox() {
                    return this.boxes.find((b) => b.id === this.uploadBoxId) || null;
                },

                get doc() {
                    if (!this.statusId) return null;
                    for (const b of this.boxes) {
                        const found = b.documents.find((d) => d.id === this.statusId);
                        if (found) return found;
                    }
                    return null;
                },

                init() {
                    // Validation failed on the server: reopen the upload modal for the same box
                    if (cfg.reopenBoxId && this.boxes.some((b) => b.id === cfg.reopenBoxId && b.can_upload)) {
                        this.openUpload(cfg.reopenBoxId);
                    }
                },

                openUpload(id) {
                    const b = this.boxes.find((x) => x.id === id);
                    if (!b) return;
                    this.uploadBoxId = id;
                    this.docType = b.type;   // pre-filled with the box type, like the old page
                    this.fileName = '';
                    this.clientError = '';
                },

                closeUpload() {
                    this.uploadBoxId = null;
                    this.fileName = '';
                    this.clientError = '';
                    if (this.$refs.file) this.$refs.file.value = '';
                },

                // Same quick checks as the old JS; the server re-validates everything
                validate(event) {
                    const file = this.$refs.file.files[0];
                    if (!file) {
                        event.preventDefault();
                        this.clientError = 'Please choose a file to upload.';
                    } else if (file.size > 10 * 1024 * 1024) {
                        event.preventDefault();
                        this.clientError = 'The selected file must be 10 MB or smaller.';
                    }
                },

                badge(status) {
                    return {
                        Approved: 'bg-[#dcfce7] text-[#166534]',
                        Rejected: 'bg-[#fee2e2] text-[#991b1b]',
                    }[status] || 'bg-[#ffedd5] text-[#92400e]';
                },
            };
        }
    </script>

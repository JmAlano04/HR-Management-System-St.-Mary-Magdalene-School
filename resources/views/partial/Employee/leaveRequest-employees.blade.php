{{-- resources/views/leave-requests/employee.blade.php (Employee: leave balances + request leave) --}}

      <x-slot name="header">
        <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight ml-[80px] text-red-700">Leave Request</h1>
    </x-slot>

    @php
        $labelClass = 'text-[0.83rem] font-bold text-[#111827]';
        $fieldClass = 'w-full rounded-xl border border-[#e8e0e0] bg-[#fffdfd] px-3.5 py-3 text-gray-900 outline-none transition focus:border-[#b22222] focus:ring-4 focus:ring-[#b22222]/10';
        $errClass   = 'text-xs font-semibold text-red-600';
        $unknown    = $balance && $balance['status'] === 'unknown';
        $captions   = config('leave.captions');
    @endphp

    <div class="mx-auto max-w-[1920px] px-4 pb-12 pt-6 sm:px-8 lg:px-12"
        x-data="leaveRequests(@js([
            'requests'    => $requests,
            'remaining'   => $remaining,
            'openRequest' => $errors->any(),
            'type'        => old('leave_type', ''),
            'start'       => old('start_date', ''),
            'end'         => old('end_date', ''),
        ]))"
        @keydown.escape.window="detail = null; requestOpen = false">

   
        @unless ($employee)
            <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                Your account is not linked to an employee record yet, so you cannot file a request. Please contact HR.
            </div>
        @endunless

        {{-- ============ Balance cards ============ --}}
        @if ($balance)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @php
                    $cards = [['Total Leave Balance', $balance['total']['remaining'], 'listed days']];
                    foreach ($balance['types'] as $type => $row) {
                        $cards[] = [$type, $row['remaining'], $captions[$type] ?? 'days'];
                    }
                @endphp

                @foreach ($cards as [$label, $value, $caption])
                    <article class="flex min-h-[132px] flex-col justify-center gap-1 rounded-[18px] border px-4 py-[18px]
                        {{ $value === 0 ? 'border-[#b22222]/30 bg-[#fef2f2]' : 'border-gray-900/10 bg-white/70' }}">
                        <span class="text-[0.9rem] font-semibold text-gray-600">{{ $label }}</span>
                        <span class="text-[2.4rem] font-extrabold leading-none {{ $value === 0 ? 'text-[#991b1b]' : 'text-[#111827]' }}">{{ $value }}</span>
                        <small class="text-[0.82rem] {{ $value === 0 ? 'text-[#991b1b]' : 'text-gray-500' }}">{{ $caption }}</small>
                    </article>
                @endforeach
            </div>
        @endif

        @if ($unknown)
            <p class="mt-3.5 rounded-xl border border-[#b22222]/20 bg-[#fff7f7] px-4 py-3 text-[0.9rem] leading-normal text-[#7f1d1d]" role="status">
                Your employment status could not be verified. Leave requests are unavailable until HR records are corrected.
            </p>
        @endif

        @if (session('success'))
            <p class="mt-3.5 text-[0.9rem] font-semibold text-[#26734d]" role="status">{{ session('success') }}</p>
        @endif

        {{-- ============ Request button ============ --}}
        <div class="mt-6 flex justify-end">
            <button type="button" @click="requestOpen = true" @disabled(! $employee || $unknown)
                class="inline-flex cursor-pointer items-center gap-2.5 rounded-[14px] bg-[#b22222] px-5 py-3 font-bold text-white shadow-[0_10px_22px_rgba(178,34,34,0.18)] transition hover:bg-[#8f1c1c] disabled:cursor-not-allowed disabled:opacity-60">
                <i class="fa-solid fa-clipboard-list"></i> Request Leave
            </button>
        </div>

        {{-- ============ Requests list ============ --}}
        <section class="mt-5 rounded-[24px] border border-[#f0e5e5] bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.08)] sm:p-7">
            <h3 class="mb-4 text-lg font-bold text-[#231f20]" x-text="tab === 'current' ? 'Current Leave Requests' : 'Archived Leave Requests'"></h3>

            <div class="mb-5 inline-flex gap-1 rounded-xl bg-[#f6f2f2] p-1" role="tablist" aria-label="Leave request views">
                <button type="button" role="tab" @click="tab = 'current'"
                    :class="tab === 'current' ? 'bg-[#b22222] text-white' : 'text-gray-700'"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] px-[18px] py-2.5 font-semibold">
                    <i class="fa-regular fa-calendar-days"></i> Current
                </button>
                <button type="button" role="tab" @click="tab = 'archive'"
                    :class="tab === 'archive' ? 'bg-[#b22222] text-white' : 'text-gray-700'"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] px-[18px] py-2.5 font-semibold">
                    <i class="fas fa-box-archive"></i> Archive
                </button>
            </div>

            <div class="grid gap-4">
                <template x-for="r in visible" :key="r.id">
                    <article tabindex="0" role="button" @click="detail = r" @keydown.enter.prevent="detail = r" @keydown.space.prevent="detail = r"
                        :aria-label="'View details for ' + r.label"
                        class="flex cursor-pointer flex-wrap items-center justify-between gap-4 rounded-2xl border-[1.5px] border-gray-900/15 bg-[#f8f9fb] px-5 py-4 transition hover:border-[#b22222]/70 hover:shadow-[0_10px_22px_rgba(15,23,42,0.04)] focus-visible:border-[#b22222]/70 focus-visible:outline-none">
                        <h4 class="m-0 text-[clamp(1.1rem,1.4vw,1.5rem)] font-bold tracking-[-0.03em] text-[#111827]" x-text="r.label"></h4>
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="inline-flex items-center gap-2">
                                <strong class="text-[0.78rem] uppercase text-gray-600">HR:</strong>
                                <span class="inline-flex min-w-[118px] justify-center rounded-full px-[18px] py-2 text-[0.95rem] font-bold" :class="badge(r.hr_status)" x-text="r.hr_status"></span>
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <strong class="text-[0.78rem] uppercase text-gray-600">Admin:</strong>
                                <span class="inline-flex min-w-[118px] justify-center rounded-full px-[18px] py-2 text-[0.95rem] font-bold" :class="badge(r.admin_status)" x-text="r.admin_status"></span>
                            </span>
                        </div>
                    </article>
                </template>

                <p x-show="! visible.length" x-cloak class="py-6 text-center text-sm text-gray-500"
                    x-text="tab === 'current' ? 'No leave requests yet.' : 'No archived leave requests.'"></p>
            </div>
        </section>

        {{-- ============ Details modal ============ --}}
        <div x-show="detail" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="detail = null">
            <div class="relative w-full max-w-[520px] rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-label="Leave details">
                <button type="button" @click="detail = null" aria-label="Close leave details"
                    class="absolute right-4 top-4 grid h-8 w-8 cursor-pointer place-items-center rounded-lg bg-[#f9e8e8] text-[#b22222]">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <span class="text-xs font-bold uppercase tracking-wide text-[#b22222]">Leave Details</span>

                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="grid grid-cols-[110px_1fr] gap-3"><dt class="font-bold text-gray-500">Leave Type</dt><dd class="text-gray-900" x-text="detail?.label"></dd></div>
                    <div class="grid grid-cols-[110px_1fr] gap-3"><dt class="font-bold text-gray-500">Requested</dt><dd class="text-gray-900" x-text="detail?.requested"></dd></div>
                    <div class="grid grid-cols-[110px_1fr] gap-3"><dt class="font-bold text-gray-500">Duration</dt><dd class="text-gray-900" x-text="detail?.range"></dd></div>
                    <div class="grid grid-cols-[110px_1fr] gap-3"><dt class="font-bold text-gray-500">Reason</dt><dd class="whitespace-pre-line text-gray-900" x-text="detail?.reason"></dd></div>
                </dl>

                <form method="POST" :action="detail?.archive_url" x-show="detail && ! detail.archived" class="mt-6 flex justify-end">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] bg-[#f9e8e8] px-4 py-2.5 font-bold text-[#b22222] hover:bg-[#f3d4d4]">
                        <i class="fa-solid fa-box-archive"></i> Archive
                    </button>
                </form>
            </div>
        </div>

        {{-- ============ Request Leave modal ============ --}}
        <div x-show="requestOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-black/40 p-4" @click.self="requestOpen = false">
            <div class="w-full max-w-[560px] rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="requestLeaveModalTitle">
                <h2 id="requestLeaveModalTitle" class="mb-4 text-xl font-extrabold text-[#231f20]">Request Leave</h2>

                <form method="POST" action="{{ route('leave-requests.store') }}" class="grid gap-4">
                    @csrf

                    <label class="grid gap-2">
                        <span class="{{ $labelClass }}">Leave Type</span>
                        <select name="leave_type" x-model="type" required class="{{ $fieldClass }} cursor-pointer">
                            <option value="" disabled>Select</option>
                            @foreach ($remaining as $type => $days)
                                <option value="{{ $type }}" @disabled($days === 0)>
                                    {{ $type }}{{ $days === 0 ? ' (no days remaining)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="min-h-[1.25em] text-[0.88rem]" :class="feedback.error ? 'font-bold text-red-700' : 'text-gray-500'" x-text="feedback.text" aria-live="polite"></p>
                        <span class="{{ $errClass }}">{{ $errors->first('leave_type') }}</span>
                    </label>

                    <label class="grid gap-2" x-show="type === 'Other'" x-cloak>
                        <span class="{{ $labelClass }}">Specific Leave Request</span>
                        <input type="text" name="other_leave_type" value="{{ old('other_leave_type') }}" maxlength="100"
                            :required="type === 'Other'" placeholder="Enter specific leave request" class="{{ $fieldClass }}">
                        <span class="{{ $errClass }}">{{ $errors->first('other_leave_type') }}</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid min-w-0 gap-2">
                            <span class="{{ $labelClass }}">Start Date</span>
                            <input type="date" name="start_date" x-model="start" :max="end || null" required class="{{ $fieldClass }}">
                            <span class="{{ $errClass }}">{{ $errors->first('start_date') }}</span>
                        </label>
                        <label class="grid min-w-0 gap-2">
                            <span class="{{ $labelClass }}">End Date</span>
                            <input type="date" name="end_date" x-model="end" :min="start || null" required class="{{ $fieldClass }}">
                            <span class="{{ $errClass }}">{{ $errors->first('end_date') }}</span>
                        </label>
                    </div>

                    <label class="grid gap-2">
                        <span class="{{ $labelClass }}">Reason</span>
                        <textarea name="reason" rows="3" maxlength="1000" required class="{{ $fieldClass }} resize-y">{{ old('reason') }}</textarea>
                        <span class="{{ $errClass }}">{{ $errors->first('reason') }}</span>
                    </label>

                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" @click="requestOpen = false" class="cursor-pointer rounded-[10px] bg-gray-100 px-5 py-2.5 font-bold text-gray-700 hover:bg-gray-200">Cancel</button>
                        <button type="submit" class="cursor-pointer rounded-[10px] bg-[#b22222] px-5 py-2.5 font-bold text-white hover:bg-[#8f1c1c]">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function leaveRequests(cfg) {
            return {
                tab: 'current',
                requests: cfg.requests,
                remaining: cfg.remaining,   // { 'Sick Leave': 3, ..., 'Other': 25 }
                detail: null,
                requestOpen: cfg.openRequest,
                type: cfg.type,
                start: cfg.start,
                end: cfg.end,

                get visible() {
                    return this.requests.filter(r => this.tab === 'archive' ? r.archived : ! r.archived);
                },

                // Inclusive calendar days (same as the original script)
                get days() {
                    if (! this.start || ! this.end) return 0;
                    const diff = (new Date(this.end + 'T00:00:00') - new Date(this.start + 'T00:00:00')) / 86400000;
                    return diff >= 0 ? Math.floor(diff) + 1 : 0;
                },

                // Live hint only. The server validates the balance again.
                get feedback() {
                    if (! this.type) return { text: '', error: false };
                    const left = this.remaining[this.type];
                    if (left === undefined || left === null) return { text: 'No balance is configured for this leave type.', error: true };
                    if (left === 0) return { text: 'There are no days remaining for this leave type.', error: true };
                    if (this.days > left) {
                        return { text: `You have ${left} day${left === 1 ? '' : 's'} remaining, but selected ${this.days} days.`, error: true };
                    }
                    return { text: `${left} day${left === 1 ? '' : 's'} remaining for this leave type.`, error: false };
                },

                badge(status) {
                    return {
                        Approved: 'bg-[#dfeec2] text-[#1d5f3a]',
                        Rejected: 'bg-[#fee2e2] text-[#991b1b]',
                    }[status] || 'bg-[#f4e2ad] text-[#8a6507]';
                },
            };
        }
    </script>

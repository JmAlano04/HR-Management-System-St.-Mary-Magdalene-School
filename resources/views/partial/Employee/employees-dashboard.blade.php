{{-- resources/views/dashboard/employee.blade.php --}}

<x-slot name="header">
    <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-4xl font-extrabold leading-tight ml-[80px] text-red-700">Dashboard</h1>
</x-slot>
    {{-- Browser tab title --}}

    <div
        class="mx-auto max-w-[1920px] px-4 pb-12 pt-6 sm:px-8 lg:px-12"
        x-data="employeeDashboard(@js([
            'slides' => $slides,
            'today'  => $today,
            'routes' => [
                'tasks' => route('tasks.index'),
            ],
        ]))"
        @keydown.escape.window="closeTasks()"
    >

        {{-- Page heading --}}
        <h1 class="mb-5 text-[clamp(2rem,3vw,3.2rem)] font-extrabold leading-[0.98] tracking-[-0.04em] text-[#b22222]"></h1>

        {{-- ============ Promo banner (read-only) ============ --}}
        <section class="relative mb-6 overflow-hidden p-4" aria-label="Promotions"
            @mouseenter="paused = true" @mouseleave="paused = false">
            <div class="relative overflow-hidden rounded-[20px]">
                <template x-for="(s, i) in slides" :key="i">
                    <div x-show="i === current" class="relative aspect-[16/6.4] min-h-[300px] overflow-hidden rounded-[20px] bg-white">
                        <img :src="s.url" :alt="s.alt" class="h-full w-full object-cover object-center saturate-[1.02] contrast-[1.02]">
                    </div>
                </template>
            </div>

            <div class="absolute bottom-[26px] left-1/2 z-[2] flex -translate-x-1/2 gap-2.5">
                <template x-for="(s, i) in slides" :key="'dot' + i">
                    <button type="button" @click="current = i" :aria-label="'Go to slide ' + (i + 1)"
                        class="h-[11px] cursor-pointer rounded-full transition-all duration-200"
                        :class="i === current ? 'w-[15px] bg-[#b22222] sm:w-[22px]' : 'w-[11px] bg-white'"></button>
                </template>
            </div>
        </section>

        <div class="grid items-stretch gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(320px,400px)]">

            {{-- ============ Calendar with personal tasks ============ --}}
            <section class="flex flex-col rounded-[22px] bg-white p-[18px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]" aria-label="Calendar">
                <div class="mb-3 flex items-center justify-between gap-4">
                    <h2 id="calendarHeading" class="text-[1.8rem] font-extrabold text-gray-900" x-text="selectedLabel"></h2>
                </div>

                <div class="grid w-full flex-1 gap-2 rounded-[28px] border border-[#f0eef0] bg-white p-3">
                    <div class="grid grid-cols-7 gap-[7px]">
                        <template x-for="d in weekdays" :key="d">
                            <span class="grid min-h-[32px] place-items-center text-[0.72rem] font-bold uppercase tracking-[0.12em] text-gray-400" x-text="d"></span>
                        </template>
                    </div>

                    <div class="grid auto-rows-[68px] grid-cols-7 gap-[7px]">
                        <template x-for="(c, i) in cells" :key="i">
                            <div>
                                <template x-if="c === null"><div class="h-full"></div></template>

                                <template x-if="c !== null">
                                    <button type="button" @click="openTasks(c.iso)"
                                        class="grid h-full w-full cursor-pointer grid-rows-[auto_1fr] items-start overflow-hidden rounded-[14px] border p-[7px] text-left text-gray-900 transition duration-200 hover:-translate-y-px hover:shadow-[0_12px_24px_rgba(15,23,42,0.08)]"
                                        :class="c.iso === selected
                                            ? 'border-[#fca5a5] bg-[#f7f2f2]'
                                            : (c.iso === today ? 'border-[#fbcaca] bg-[#fff7f7]' : 'border-[#ececec] bg-white')">
                                        <div class="grid w-full gap-px">
                                            <span class="text-base font-extrabold leading-none"
                                                :class="c.iso === selected ? 'text-[#991b1b]' : (c.iso === today ? 'text-[#b22222]' : 'text-gray-900')"
                                                x-text="c.day"></span>
                                            <span x-show="taskCount(c.iso) > 0"
                                                class="overflow-hidden text-ellipsis whitespace-nowrap text-[0.78rem] leading-[1.1] text-gray-500"
                                                x-text="taskCount(c.iso) + (taskCount(c.iso) === 1 ? ' task' : ' tasks')"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            {{-- ============ Upcoming birthdays ============ --}}
            <aside aria-labelledby="birthdaysHeading" class="rounded-[22px] bg-white p-[22px] shadow-[0_10px_30px_rgba(15,23,42,0.05)]">
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div>
                        <p class="mb-1.5 text-[0.7rem] font-extrabold uppercase tracking-[0.12em] text-[#b22222]">Upcoming</p>
                        <h2 id="birthdaysHeading" class="text-[1.35rem] font-extrabold text-gray-900">Employee Birthdays</h2>
                    </div>
                    <i class="fa-solid fa-cake-candles flex h-14 w-14 shrink-0 items-center justify-center text-[40px] leading-none text-[#b22222]" aria-hidden="true"></i>
                </div>

                @if (count($birthdays) === 0)
                    <div class="grid justify-items-center gap-2 rounded-[14px] bg-[#fff7f7] px-4 py-[34px] text-center text-gray-400">
                        <i class="fa-regular fa-calendar text-2xl text-[#b22222]" aria-hidden="true"></i>
                        <p class="text-[0.85rem]">No upcoming employee birthdays.</p>
                    </div>
                @else
                    <div class="grid max-h-[520px] gap-2.5 overflow-y-auto [-ms-overflow-style:none] [scrollbar-width:none]
           [&::-webkit-scrollbar]:hidden">
                        @foreach ($birthdays as $i => $b)
                            <article @class([
                                'grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3',
                                '-mx-2.5 rounded-xl bg-[#fff7f7] px-2.5 py-3' => $i === 0,
                                'border-b border-[#f1f1f1] py-3 last:border-b-0' => $i !== 0,
                            ])>
                                <div class="grid h-[38px] w-[38px] place-items-center rounded-full bg-[#fce2e2] text-[0.75rem] font-extrabold text-[#991b1b]" aria-hidden="true">{{ $b['initials'] }}</div>
                                <div class="min-w-0">
                                    <h3 class="truncate text-[0.9rem] font-extrabold text-gray-900">{{ $b['name'] }}</h3>
                                    <p class="mt-[3px] truncate text-[0.75rem] text-gray-400">{{ $b['role'] }}</p>
                                </div>
                                <time datetime="{{ $b['iso'] }}" class="whitespace-nowrap text-[0.8rem] font-extrabold text-[#b22222]">{{ $b['date_label'] }}</time>
                            </article>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>

        {{-- ============ Tasks modal ============ --}}
        <div x-show="taskDate" x-cloak x-transition.opacity @click.self="closeTasks()"
            class="fixed inset-0 z-[60] grid place-items-center bg-black/50 p-5">
            <div role="dialog" aria-modal="true" aria-labelledby="taskModalTitle"
                class="w-full max-w-[520px] overflow-hidden rounded-3xl bg-white shadow-[0_28px_70px_rgba(15,23,42,0.2)]">

                <div class="flex items-center justify-between border-b border-[#ecebea] px-6 py-[22px]">
                    <h3 id="taskModalTitle" class="text-[1.2rem] font-bold text-gray-900" x-text="'Tasks for ' + taskDateLabel"></h3>
                    <button type="button" @click="closeTasks()" aria-label="Close"
                        class="h-[38px] w-[38px] cursor-pointer rounded-full text-[1.4rem] text-[#b22222] transition hover:bg-red-50">&times;</button>
                </div>

                <div class="p-6">
                    <form @submit.prevent="addTask()" class="mb-[18px] grid grid-cols-[1fr_auto] gap-2.5">
                        <input type="text" x-model="newTask" x-ref="taskInput" maxlength="255" placeholder="Add a new task"
                            class="w-full rounded-[14px] border border-gray-300 bg-gray-50 px-4 py-3.5 text-gray-900 focus:border-[#b22222] focus:outline-none focus:ring-4 focus:ring-red-700/10">
                        <button type="submit" :disabled="saving"
                            class="cursor-pointer rounded-[14px] bg-[#dc2626] px-4 py-3 font-bold text-white transition hover:-translate-y-px disabled:cursor-not-allowed disabled:opacity-60">Add</button>
                    </form>
                    <p class="-mt-3 mb-3 text-xs font-semibold text-red-600" x-text="error"></p>

                    <div class="grid gap-3">
                        <p x-show="tasksForDate.length === 0" class="text-gray-500">No tasks for this date. Add a new task below.</p>

                        <template x-for="t in tasksForDate" :key="t.id">
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-[#e5e7eb] bg-[#f8fafc] px-3.5 py-3">
                                <label class="flex min-w-0 items-center gap-2.5">
                                    <input type="checkbox" class="h-[18px] w-[18px] cursor-pointer accent-[#b22222]"
                                        @change="if ($event.target.checked) removeTask(t)">
                                    <span class="inline-block min-w-0 overflow-hidden text-ellipsis whitespace-nowrap font-semibold text-gray-900" x-text="t.title"></span>
                                </label>
                                <button type="button" @click="removeTask(t)"
                                    class="cursor-pointer rounded-[14px] border border-gray-300 bg-white/95 px-4 py-3 font-bold text-[#b22222] transition hover:-translate-y-px hover:border-[#8f241f]/30 hover:bg-white">Remove</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function employeeDashboard(cfg) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const pad = (n) => String(n).padStart(2, '0');

            const readError = async (res) => {
                const json = await res.json().catch(() => ({}));
                if (json.errors) return Object.values(json.errors)[0][0];
                return json.message || 'Something went wrong. Please try again.';
            };

            return {
                // ---- banner ----
                slides: cfg.slides,
                current: 0,
                paused: false,

                // ---- calendar ----
                weekdays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                today: cfg.today,
                selected: cfg.today,
                year: 0,
                month: 0,
                tasks: {},          // { '2026-10-19': [ {id, title, date}, ... ] }

                // ---- tasks modal ----
                taskDate: null,
                newTask: '',
                error: '',
                saving: false,

                init() {
                    const [y, m] = cfg.today.split('-').map(Number);
                    this.year = y;
                    this.month = m - 1;

                    // Auto-advance the banner every 4.5 seconds
                    setInterval(() => {
                        if (!this.paused && this.slides.length > 1) {
                            this.current = (this.current + 1) % this.slides.length;
                        }
                    }, 4500);

                    this.loadTasks();
                },

                // ===== Calendar =====
                get selectedLabel() {
                    return new Date(this.selected + 'T00:00:00').toLocaleDateString('en-US', {
                        month: 'long', day: 'numeric', year: 'numeric',
                    });
                },

                get cells() {
                    const firstWeekday = new Date(this.year, this.month, 1).getDay();
                    const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                    const out = [];
                    for (let i = 0; i < firstWeekday; i++) out.push(null);
                    for (let d = 1; d <= daysInMonth; d++) {
                        out.push({ day: d, iso: `${this.year}-${pad(this.month + 1)}-${pad(d)}` });
                    }
                    return out;
                },

                taskCount(iso) { return (this.tasks[iso] || []).length; },

                async loadTasks() {
                    const month = `${this.year}-${pad(this.month + 1)}`;
                    try {
                        const res = await fetch(`${cfg.routes.tasks}?month=${month}`, { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) throw new Error('failed');
                        const map = {};
                        (await res.json()).forEach((t) => { (map[t.date] = map[t.date] || []).push(t); });
                        this.tasks = map;
                    } catch (e) {
                        this.tasks = {};
                    }
                },

                // ===== Tasks modal =====
                get taskDateLabel() {
                    return this.taskDate
                        ? new Date(this.taskDate + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
                        : '';
                },

                get tasksForDate() { return this.taskDate ? (this.tasks[this.taskDate] || []) : []; },

                openTasks(iso) {
                    this.selected = iso;
                    this.taskDate = iso;
                    this.newTask = '';
                    this.error = '';
                    this.$nextTick(() => this.$refs.taskInput && this.$refs.taskInput.focus());
                },

                closeTasks() { this.taskDate = null; },

                async addTask() {
                    const title = this.newTask.trim();
                    if (!title) return;

                    this.saving = true;
                    this.error = '';
                    try {
                        const res = await fetch(cfg.routes.tasks, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ title, task_date: this.taskDate }),
                        });
                        if (!res.ok) { this.error = await readError(res); return; }

                        const t = await res.json();
                        this.tasks = { ...this.tasks, [t.date]: [...(this.tasks[t.date] || []), t] };
                        this.newTask = '';
                    } catch (e) {
                        this.error = 'Could not save the task. Please try again.';
                    } finally {
                        this.saving = false;
                    }
                },

                // Used by both the "Remove" button and the checkbox (task completed)
                async removeTask(t) {
                    try {
                        const res = await fetch(`${cfg.routes.tasks}/${t.id}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        });
                        if (!res.ok) return;
                        this.tasks = { ...this.tasks, [t.date]: (this.tasks[t.date] || []).filter((x) => x.id !== t.id) };
                    } catch (e) {
                        /* leave the task in place */
                    }
                },
            };
        }
    </script>

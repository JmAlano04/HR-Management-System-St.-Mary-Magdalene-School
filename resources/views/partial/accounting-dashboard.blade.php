@props([
    'taskKey'   => 'dashboardTasks',
    'bannerSlides' => [],
])
 @auth
    <x-slot name="title">
     
        @if (auth()->user()->hasRole('Accounting'))
            Accounting Dashboard
       
        @endif
    </x-slot>

  
@endauth
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

<div class="admin-dashboard pb-12 ml-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <main class="main-content relative min-w-0 pt-[34px] pb-[42px] px-[clamp(20px,2.5vw,42px)] max-[960px]:pt-[82px]">

            {{-- Promo banner --}}
            <section class="promo-banner relative mb-6 p-4 rounded-[28px] overflow-hidden max-[720px]:p-3 max-[720px]:rounded-3xl">
                <div class="promo-slides relative overflow-hidden rounded-[20px]" id="promoSlides">
                    @foreach ($bannerSlides as $i => $slide)
                        <div class="promo-slide hidden [&.active]:block {{ $i === 0 ? 'active' : '' }}">
                            <div class="promo-slide-image relative aspect-[16/6.4] min-h-[300px] overflow-hidden rounded-[20px] bg-white max-[720px]:min-h-[220px] max-[720px]:aspect-[4/3]">
                                <img src="{{ $slide['url'] }}" alt="{{ $slide['alt'] }}" class="block w-full h-full object-cover object-center" />
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="promo-controls absolute left-1/2 bottom-[26px] -translate-x-1/2 flex gap-2.5 z-[2]" id="promoControls"></div>
            </section>

            {{-- Calendar --}}
            <div class="dashboard-content grid grid-cols-1 gap-6 items-start">
                <section class="calendar-panel p-7 bg-white max-[720px]:p-3.5">
                    <div class="calendar-header flex justify-between items-center gap-4 mb-3">
                        <h2 id="calendarHeading" class="m-0 text-gray-900 text-[1.8rem] font-extrabold"></h2>
                    </div>
                    <div class="calendar-widget grid gap-3.5 p-5 w-full bg-white rounded-[28px] border border-[#f0eef0] max-[720px]:p-2.5">
                        <div class="calendar-weekdays grid grid-cols-[repeat(7,minmax(0,1fr))] gap-3 max-[720px]:gap-[5px]">
                            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)
                                <span class="grid items-center justify-center min-h-[42px] text-gray-400 font-bold uppercase tracking-[0.12em] text-[0.72rem]">{{ $d }}</span>
                            @endforeach
                        </div>
                        <div class="calendar-grid grid grid-cols-[repeat(7,minmax(0,1fr))] auto-rows-[minmax(110px,1fr)] gap-3.5 max-[720px]:gap-[5px] max-[720px]:auto-rows-[minmax(64px,1fr)]" id="calendarGrid"></div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<script>
(function () {
    const promoSlidesFromServer = @json(collect($bannerSlides)->map(fn ($slide) => ['id' => $slide['id'], 'image' => $slide['url'], 'alt' => $slide['alt']])->values());
    const bannerRefreshUrl = @json(route('dashboard.banners.current'));
    const taskStorageKey = @json($taskKey);

    const TW = {
        redBtn: "red-btn rounded-[14px] border border-transparent bg-[#dc2626] text-white px-4 py-3 font-bold cursor-pointer transition duration-200 hover:-translate-y-px active:translate-y-px active:scale-[0.98]",
        clearBtn: "clear-btn rounded-[14px] border border-gray-300 bg-white text-[#b22222] px-4 py-3 font-bold cursor-pointer transition duration-200 hover:-translate-y-px hover:border-[rgba(143,36,31,0.28)] active:translate-y-px active:scale-[0.98]",
        promoDot: "w-[11px] h-[11px] border-0 rounded-full bg-white cursor-pointer [&.active]:w-[15px] [&.active]:bg-[#b22222]",
        dayBase: "calendar-day grid grid-rows-[auto_1fr] items-start relative overflow-hidden aspect-square p-2.5 rounded-[20px] border bg-white text-gray-900 cursor-pointer transition duration-200 hover:-translate-y-px hover:shadow-[0_12px_24px_rgba(15,23,42,0.08)] max-[720px]:p-[7px] max-[720px]:rounded-xl",
        dayNormal: "border-[#ececec]",
        dayToday: "today border-[#fbcaca] bg-[#fff7f7]",
        daySelected: "selected border-[#fca5a5] bg-[#f7f2f2]",
        numNormal: "text-gray-900",
        numToday: "text-[#b22222]",
        numSelected: "text-[#991b1b]",
        taskModal: "task-modal fixed inset-0 z-[1100] flex items-center justify-center p-5 bg-black/50",
        taskModalContent: "task-modal-content w-full max-w-[520px] overflow-hidden bg-white rounded-3xl shadow-[0_28px_70px_rgba(15,23,42,0.2)]",
        taskModalHeader: "task-modal-header flex justify-between items-center gap-4 px-6 py-[22px] border-b border-[#ecebea]",
        taskModalTitle: "m-0 text-gray-900 text-[1.17em] font-bold",
        taskClose: "close-btn w-[38px] h-[38px] border-0 rounded-full bg-white/90 text-[#b22222] text-[1.4rem] cursor-pointer transition-transform hover:text-[#dc2626] hover:scale-[1.08]",
        taskModalBody: "task-modal-body p-6",
        taskInputRow: "task-input-row grid grid-cols-[1fr_auto] gap-2.5 mb-[18px]",
        taskInput: "w-full px-4 py-3.5 rounded-[14px] border border-gray-300 bg-gray-50",
        taskList: "task-list grid gap-3",
        taskItem: "task-item flex justify-between items-center gap-3 px-3.5 py-3 rounded-2xl bg-slate-50 border border-gray-200",
        taskTitle: "text-gray-900 font-semibold",
        emptyTask: "empty-task m-0 text-[#6b7280]",
    };

    const escapeHtml = (s) => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    // ---------- Banner ----------
    const promoSlides = document.getElementById("promoSlides");
    const promoControls = document.getElementById("promoControls");
    let currentPromoSlides = promoSlidesFromServer;
    let promoIndex = 0, promoIntervalId = null;

    function renderPromoBanner() {
        const slides = currentPromoSlides;
        promoSlides.innerHTML = slides.map((slide, i) => `
            <div class="promo-slide hidden [&.active]:block ${i === 0 ? "active" : ""}">
                <div class="promo-slide-image relative aspect-[16/6.4] min-h-[300px] overflow-hidden rounded-[20px] bg-white max-[720px]:min-h-[220px] max-[720px]:aspect-[4/3]"><img class="block w-full h-full object-cover object-center" src="${slide.image}" alt="${escapeHtml(slide.alt)}" /></div>
            </div>`).join("");
        promoControls.innerHTML = slides.map((s, i) => `<button type="button" class="${TW.promoDot} ${i === 0 ? "active" : ""}" aria-label="Show banner ${i + 1}"></button>`).join("");
        promoControls.querySelectorAll("button").forEach((b, i) => b.addEventListener("click", () => {
            promoIndex = i; updatePromoBanner(); startPromoTimer(slides.length);
        }));
        promoIndex = 0;
        startPromoTimer(slides.length);
    }

    function updatePromoBanner() {
        promoSlides.querySelectorAll(".promo-slide").forEach((s, i) => s.classList.toggle("active", i === promoIndex));
        promoControls.querySelectorAll("button").forEach((b, i) => b.classList.toggle("active", i === promoIndex));
    }

    function startPromoTimer(count) {
        clearInterval(promoIntervalId);
        if (count < 2) return;
        promoIntervalId = setInterval(() => {
            promoIndex = (promoIndex + 1) % count;
            updatePromoBanner();
        }, 4500);
    }

    function samePromoSlides(first, second) {
        return first.length === second.length && first.every((slide, index) =>
            slide.id === second[index].id && slide.image === second[index].image && slide.alt === second[index].alt
        );
    }

    async function refreshDashboardBanners() {
        try {
            const response = await fetch(bannerRefreshUrl, {
                headers: { Accept: "application/json" },
                cache: "no-store",
            });
            if (!response.ok) throw new Error(`Banner refresh failed with status ${response.status}.`);
            const data = await response.json();
            if (!Array.isArray(data.slides)) throw new Error("Banner refresh returned an invalid response.");
            const slides = data.slides.map((slide) => ({
                id: slide.id,
                image: slide.url,
                alt: slide.alt,
            }));
            if (!samePromoSlides(currentPromoSlides, slides)) {
                currentPromoSlides = slides;
                renderPromoBanner();
            }
        } catch (error) {
            console.warn("Unable to refresh dashboard banners; retrying.", error);
        } finally {
            window.setTimeout(refreshDashboardBanners, 3000);
        }
    }

    // ---------- Calendar ----------
    const calendarGrid = document.getElementById("calendarGrid");
    const calendarHeading = document.getElementById("calendarHeading");
    let tasks = JSON.parse(localStorage.getItem(taskStorageKey) || "[]");
    let currentDate = new Date();
    let selectedDate = new Date();

    const formatDay = d => d.toLocaleDateString("en-US", { year: "numeric", month: "2-digit", day: "2-digit" });
    const isToday = d => formatDay(d) === formatDay(new Date());
    const saveTasks = () => localStorage.setItem(taskStorageKey, JSON.stringify(tasks));

    function renderCalendar() {
        const month = currentDate.getMonth();
        const year = currentDate.getFullYear();
        const firstDay = new Date(year, month, 1).getDay();
        const totalDays = new Date(year, month + 1, 0).getDate();
        calendarHeading.textContent = selectedDate.toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" });
        calendarGrid.innerHTML = "";
        for (let i = 0; i < firstDay; i++) calendarGrid.insertAdjacentHTML("beforeend", '<div class="calendar-day empty aspect-square"></div>');
        for (let day = 1; day <= totalDays; day++) {
            const dayDate = new Date(year, month, day);
            const dayTasks = tasks.filter(t => t.date === formatDay(dayDate));
            const isSelected = formatDay(dayDate) === formatDay(selectedDate);
            const today = isToday(dayDate);
            const stateClass = isSelected ? TW.daySelected : today ? TW.dayToday : TW.dayNormal;
            const numClass = isSelected ? TW.numSelected : today ? TW.numToday : TW.numNormal;
            const cell = document.createElement("button");
            cell.type = "button";
            cell.className = `${TW.dayBase} ${stateClass}`;
            cell.innerHTML = `<div class="calendar-day-content grid w-full gap-1"><span class="day-number text-base font-extrabold leading-none ${numClass}">${day}</span>${dayTasks.length ? `<span class="calendar-task-preview text-[#6b7280] text-[0.78rem] leading-[1.1] overflow-hidden text-ellipsis whitespace-nowrap max-[720px]:text-[0.64rem]">${dayTasks.length} task${dayTasks.length === 1 ? "" : "s"}</span>` : ""}</div>`;
            cell.addEventListener("click", () => { selectedDate = dayDate; renderCalendar(); openTaskModal(dayDate); });
            calendarGrid.appendChild(cell);
        }
    }

    function openTaskModal(date) {
        const dateKey = formatDay(date);
        const modal = document.createElement("div");
        modal.className = TW.taskModal;
        modal.innerHTML = `<div class="${TW.taskModalContent}"><div class="${TW.taskModalHeader}"><h3 class="${TW.taskModalTitle}">Tasks for ${date.toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" })}</h3><button type="button" class="${TW.taskClose}">&times;</button></div><div class="${TW.taskModalBody}"><div class="${TW.taskInputRow}"><input class="${TW.taskInput}" type="text" placeholder="Add a new task" /><button type="button" class="${TW.redBtn}">Add</button></div><div class="${TW.taskList}"></div></div></div>`;
        document.body.appendChild(modal);
        const list = modal.querySelector(".task-list");
        const renderTasks = () => {
            const dateTasks = tasks.filter(t => t.date === dateKey);
            list.innerHTML = dateTasks.length
                ? dateTasks.map(t => `<div class="${TW.taskItem}"><span class="${TW.taskTitle}">${escapeHtml(t.title)}</span><button type="button" class="${TW.clearBtn}">Remove</button></div>`).join("")
                : `<p class="${TW.emptyTask}">No tasks for this date. Add a new task below.</p>`;
            list.querySelectorAll("button").forEach((button, index) => button.addEventListener("click", () => {
                let removed = 0;
                tasks = tasks.filter(t => t.date !== dateKey || removed++ !== index);
                saveTasks(); renderTasks(); renderCalendar();
            }));
        };
        modal.querySelector(".red-btn").addEventListener("click", () => {
            const input = modal.querySelector("input");
            if (!input.value.trim()) return;
            tasks.push({ date: dateKey, title: input.value.trim() });
            saveTasks(); input.value = ""; renderTasks(); renderCalendar();
        });
        modal.querySelector(".close-btn").addEventListener("click", () => modal.remove());
        modal.addEventListener("click", e => { if (e.target === modal) modal.remove(); });
        renderTasks();
    }

    window.addEventListener("load", () => {
        renderPromoBanner();
        renderCalendar();
        refreshDashboardBanners();
    });
})();
</script>
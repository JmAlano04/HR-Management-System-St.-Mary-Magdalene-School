{{-- Browser tab title --}}

@auth
    <x-slot name="title">
        @if (auth()->user()->hasRole('Admin'))
            Admin Dashboard
        @elseif (auth()->user()->hasRole('HR'))
            HR Dashboard
        @elseif (auth()->user()->hasRole('Accounting'))
            Accounting Dashboard
        @elseif (auth()->user()->hasRole('Employee'))
            Employee Dashboard
        @elseif (auth()->user()->hasRole('Applicant'))
            Applicant Dashboard
        @else
            Dashboard
        @endif
    </x-slot>
@endauth

{{-- Font Awesome (for the fa-edit / upload icons) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

{{-- Defaults used by the script below --}}
<script>
    window.adminPromoDefaults = [
        { image: "{{ asset('Image/promote1.jpg') }}", alt: "School awards celebration" },
        { image: "{{ asset('Image/promote2.jpg') }}", alt: "Calendar and planning" },
        { image: "{{ asset('Image/promote3.jpg') }}", alt: "Staff recognition update" },
    ];
</script>

{{-- Mobile-first: base = phones, sm = 640px+, lg = 1024px+ --}}
<div class="admin-dashboard w-full pb-8 sm:pb-12 sm:ml-4">
    <div class="mx-auto max-w-7xl px-0 sm:px-6 lg:px-8">

        {{-- .main-content --}}
        <main class="main-content relative min-w-0 pt-[34px] pb-8 px-[clamp(12px,2.5vw,42px)] sm:pb-[42px] max-[960px]:pt-[82px]">

            {{-- .promo-banner --}}
            <section class="promo-banner relative mb-4 overflow-hidden rounded-2xl p-2 sm:mb-6 sm:rounded-[28px] sm:p-4">
                <div class="promo-slides relative overflow-hidden rounded-xl sm:rounded-[20px]" id="promoSlides">
                    @foreach (['promote1', 'promote2', 'promote3'] as $i => $img)
                        <div class="promo-slide hidden [&.active]:block {{ $i === 0 ? 'active' : '' }}">
                            <div class="promo-slide-image relative aspect-[4/3] min-h-[200px] overflow-hidden rounded-xl bg-white sm:aspect-[16/9] sm:min-h-[260px] sm:rounded-[20px] lg:aspect-[16/6.4] lg:min-h-[300px]">
                                <img src="{{ asset('Image/' . $img . '.jpg') }}" alt="{{ $img }}" class="block h-full w-full object-cover object-center" />
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="promo-controls absolute bottom-4 left-1/2 z-[2] flex -translate-x-1/2 gap-2 sm:bottom-[26px] sm:gap-2.5" id="promoControls"></div>
                <button id="editBannerBtn" aria-label="Edit banner images"
                    class="edit-banner-btn absolute right-4 top-4 z-[3] grid h-10 w-10 cursor-pointer place-items-center rounded-full border-0 bg-white/90 text-gray-900 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:right-[26px] sm:top-[26px] sm:h-11 sm:w-11" type="button">
                    <i class="fas fa-edit"></i>
                </button>
            </section>

            {{-- Banner modal (bottom sheet on phones, centered dialog on sm+) --}}
            <div id="bannerModal" class="modal hidden fixed inset-0 z-[1000] flex items-end justify-center overflow-y-auto bg-black/50 p-0 backdrop-blur-lg sm:items-center sm:p-5 [&.hidden]:hidden">
                <div class="modal-content banner-modal-content flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-3xl bg-white shadow-[0_28px_70px_rgba(15,23,42,0.2)] sm:max-h-[calc(100dvh-40px)] sm:max-w-[720px] sm:rounded-3xl" role="dialog" aria-modal="true" aria-labelledby="bannerModalTitle">
                    <div class="modal-header flex shrink-0 items-center justify-between gap-3 border-b border-[#ecebea] px-4 pb-4 pt-5 sm:gap-4 sm:px-7 sm:pb-5 sm:pt-6">
                        <h3 id="bannerModalTitle" class="m-0 min-w-0 text-base font-bold text-gray-900 sm:text-[1.17rem]">Edit Banner Images</h3>
                        <button id="closeBannerModal" aria-label="Close" class="close-btn h-10 w-10 shrink-0 cursor-pointer rounded-full border-0 bg-transparent text-[1.4rem] text-[#b22222] transition-transform hover:scale-[1.08] hover:text-[#dc2626] active:scale-[0.94] sm:h-[38px] sm:w-[38px]">&times;</button>
                    </div>
                    <div class="modal-body min-h-0 flex-1 overflow-y-auto px-4 pb-5 pt-4 sm:px-7 sm:pb-7 sm:pt-6">
                        <div class="upload-section grid gap-3">
                            <div class="upload-area relative cursor-pointer overflow-hidden rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-center transition duration-200 hover:-translate-y-px hover:border-[#dc2626] hover:bg-[#fef2f2] sm:rounded-[22px] sm:px-5 sm:py-[34px]" id="uploadArea">
                                <div class="upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt mb-2 block text-4xl text-gray-400 sm:mb-3 sm:text-[2.8rem]"></i>
                                    <p class="m-0 mb-1.5 text-sm font-bold text-gray-700 sm:mb-2 sm:text-base">Tap to upload images<span class="hidden sm:inline"> or drag and drop</span></p>
                                    <span class="text-[0.82rem] text-[#6b7280] sm:text-[0.92rem]">PNG or JPEG images</span>
                                </div>
                                <input type="file" id="imageUpload" class="absolute inset-0 cursor-pointer opacity-0" multiple accept="image/png,image/jpeg" />
                            </div>
                            <div class="upload-info flex items-center justify-end gap-3">
                                <p id="imageCount" class="m-0 mr-auto text-[0.85rem] text-[#6b7280] sm:text-[0.92rem]">0 images selected</p>
                            </div>
                            <div class="uploaded-images grid grid-cols-2 gap-2.5 min-[480px]:grid-cols-3 sm:grid-cols-[repeat(auto-fill,minmax(140px,1fr))] sm:gap-3.5" id="uploadedImages"></div>
                        </div>
                        <div class="modal-footer mt-3 flex flex-col gap-2.5 pt-1.5 sm:mt-1 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                            <button type="button" id="resetToDefaults" class="white-btn w-full cursor-pointer rounded-[14px] border border-gray-300 bg-white px-4 py-3 font-bold text-[#b22222] transition duration-200 hover:-translate-y-px hover:border-[rgba(143,36,31,0.28)] active:translate-y-px active:scale-[0.98] sm:w-auto">Reset to Defaults</button>
                            <button type="button" id="saveBannerImages" class="red-btn order-first w-full cursor-pointer rounded-[14px] border border-transparent bg-[#dc2626] px-4 py-3 font-bold text-white transition duration-200 hover:-translate-y-px active:translate-y-px active:scale-[0.98] sm:order-none sm:ml-auto sm:w-auto">Save Changes</button>
                            <button type="button" id="cancelBannerEdit" class="white-btn w-full cursor-pointer rounded-[14px] border border-gray-300 bg-white px-4 py-3 font-bold text-[#b22222] transition duration-200 hover:-translate-y-px hover:border-[rgba(143,36,31,0.28)] active:translate-y-px active:scale-[0.98] sm:w-auto">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Calendar --}}
            <div class="dashboard-content grid grid-cols-1 items-start gap-4 sm:gap-6">
                <section class="calendar-panel min-w-0 bg-white p-2 sm:p-4 lg:p-7">
                    <div class="calendar-header mb-3 flex items-center justify-between gap-4">
                        <h2 id="calendarHeading" class="m-0 text-xl font-extrabold text-gray-900 sm:text-2xl lg:text-[1.8rem]">August 3, 2026</h2>
                    </div>

                    <div class="calendar-widget grid w-full gap-2 rounded-2xl border border-[#f0eef0] bg-white p-2 sm:gap-3.5 sm:rounded-[28px] sm:p-4 lg:p-5">
                        <div class="calendar-weekdays grid grid-cols-[repeat(7,minmax(0,1fr))] gap-1 sm:gap-2 lg:gap-3">
                            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)
                                <span class="grid min-h-[28px] items-center justify-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-gray-400 sm:min-h-[42px] sm:text-[0.72rem] sm:tracking-[0.12em]">
                                    <span class="sm:hidden" aria-hidden="true">{{ substr($d, 0, 1) }}</span>
                                    <span class="hidden sm:inline">{{ $d }}</span>
                                    <span class="sr-only">{{ $d }}</span>
                                </span>
                            @endforeach
                        </div>
                        <div class="calendar-grid grid auto-rows-[minmax(44px,1fr)] grid-cols-[repeat(7,minmax(0,1fr))] gap-1 sm:auto-rows-[minmax(72px,1fr)] sm:gap-2 lg:auto-rows-[minmax(110px,1fr)] lg:gap-3.5" id="calendarGrid"></div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<script>
    // Prefer the Blade-generated asset URLs for the default banners
    const bladeDefaultPromoSlides = window.adminPromoDefaults;

    // ---------- Tailwind class strings used by the dynamically-rendered markup (mobile-first) ----------
    const TW = {
        redBtn: "red-btn rounded-[14px] border border-transparent bg-[#dc2626] text-white px-4 py-3 font-bold cursor-pointer transition duration-200 hover:-translate-y-px active:translate-y-px active:scale-[0.98]",
        clearBtn: "clear-btn shrink-0 rounded-[14px] border border-gray-300 bg-white text-[#b22222] px-3 py-2 text-sm font-bold cursor-pointer transition duration-200 hover:-translate-y-px hover:border-[rgba(143,36,31,0.28)] active:translate-y-px active:scale-[0.98] sm:px-4 sm:py-3 sm:text-base",
        promoDot: "h-2.5 w-2.5 cursor-pointer rounded-full border-0 bg-white sm:h-[11px] sm:w-[11px] [&.active]:w-[15px] [&.active]:bg-[#b22222]",
        promoSlideImage: "promo-slide-image relative aspect-[4/3] min-h-[200px] overflow-hidden rounded-xl bg-white sm:aspect-[16/9] sm:min-h-[260px] sm:rounded-[20px] lg:aspect-[16/6.4] lg:min-h-[300px]",
        imagePreview: "image-preview relative overflow-hidden rounded-2xl bg-gray-100 border-2 border-gray-200 sm:rounded-[18px]",
        imagePreviewImg: "block w-full h-24 object-cover sm:h-32",
        removeImage: "remove-image absolute top-1.5 right-1.5 h-7 w-7 rounded-full border-0 bg-red-500/90 text-white cursor-pointer sm:top-2 sm:right-2 sm:h-[26px] sm:w-[26px]",
        dayBase: "calendar-day grid grid-rows-[auto_1fr] items-start relative overflow-hidden aspect-square p-1 rounded-lg border bg-white text-left text-gray-900 cursor-pointer transition duration-200 hover:-translate-y-px hover:shadow-[0_12px_24px_rgba(15,23,42,0.08)] sm:p-2 sm:rounded-xl lg:p-2.5 lg:rounded-[20px]",
        dayNormal: "border-[#ececec]",
        dayToday: "today border-[#fbcaca] bg-[#fff7f7]",
        daySelected: "selected border-[#fca5a5] bg-[#f7f2f2]",
        numNormal: "text-gray-900",
        numToday: "text-[#b22222]",
        numSelected: "text-[#991b1b]",
        taskModal: "task-modal fixed inset-0 z-[1100] flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-5",
        taskModalContent: "task-modal-content flex max-h-[90dvh] w-full flex-col overflow-hidden rounded-t-3xl bg-white shadow-[0_28px_70px_rgba(15,23,42,0.2)] sm:max-h-[calc(100dvh-40px)] sm:max-w-[520px] sm:rounded-3xl",
        taskModalHeader: "task-modal-header flex shrink-0 items-center justify-between gap-3 border-b border-[#ecebea] px-4 py-4 sm:gap-4 sm:px-6 sm:py-[22px]",
        taskModalTitle: "m-0 min-w-0 break-words text-base font-bold text-gray-900 sm:text-[1.17rem]",
        taskClose: "close-btn h-10 w-10 shrink-0 cursor-pointer rounded-full border-0 bg-white/90 text-[1.4rem] text-[#b22222] transition-transform hover:scale-[1.08] hover:text-[#dc2626] sm:h-[38px] sm:w-[38px]",
        taskModalBody: "task-modal-body min-h-0 flex-1 overflow-y-auto p-4 sm:p-6",
        taskInputRow: "task-input-row mb-4 grid grid-cols-1 gap-2.5 sm:mb-[18px] sm:grid-cols-[1fr_auto]",
        taskAddBtn: "w-full sm:w-auto",
        taskInput: "w-full min-w-0 rounded-[14px] border border-gray-300 bg-gray-50 px-4 py-3 text-base sm:py-3.5",
        taskList: "task-list grid gap-2.5 sm:gap-3",
        taskItem: "task-item flex items-center justify-between gap-2 rounded-2xl border border-gray-200 bg-slate-50 px-3 py-3 sm:gap-3 sm:px-3.5",
        taskTitle: "min-w-0 break-words text-sm font-semibold text-gray-900 sm:text-base",
        emptyTask: "empty-task m-0 text-sm text-[#6b7280] sm:text-base",
    };

    // Element references
    const sidebar = document.getElementById("sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebarBackdrop = document.getElementById("sidebarBackdrop");
    const profileToggle = document.getElementById("profileToggle");
    const profileMenu = document.getElementById("profileMenu");

    // Screen width breakpoint matching CSS @media (max-width: 960px)
    const mobileSidebarBreakpoint = window.matchMedia("(max-width: 960px)");

    function openSidebar() {
        if (!sidebar || !sidebarBackdrop) return;
        sidebar.classList.add("open");
        sidebarBackdrop.hidden = false;
        document.body.style.overflow = "hidden"; // Prevent background scrolling
        if (sidebarToggle) {
            sidebarToggle.setAttribute("aria-expanded", "true");
        }
    }

    function closeSidebar() {
        if (!sidebar || !sidebarBackdrop) return;
        sidebar.classList.remove("open");
        sidebarBackdrop.hidden = true;
        document.body.style.overflow = ""; // Restore scrolling
        if (sidebarToggle) {
            sidebarToggle.setAttribute("aria-expanded", "false");
        }
    }

    function syncSidebarState() {
        if (!mobileSidebarBreakpoint.matches) {
            closeSidebar();
            if (sidebarBackdrop) {
                sidebarBackdrop.hidden = true;
            }
            document.body.style.overflow = "";
        } else if (sidebarToggle) {
            sidebarToggle.setAttribute("aria-expanded", sidebar?.classList.contains("open") ? "true" : "false");
        }
    }

    function setupSidebarEventListeners() {
        // Toggle sidebar on hamburger button click
        if (sidebarToggle) {
            sidebarToggle.addEventListener("click", () => {
                if (sidebar?.classList.contains("open")) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        // Close sidebar when clicking the dark backdrop area
        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener("click", closeSidebar);
        }

        // Close sidebar when a navigation link inside is clicked (on mobile)
        if (sidebar) {
            sidebar.querySelectorAll("a").forEach((link) => {
                link.addEventListener("click", () => {
                    if (mobileSidebarBreakpoint.matches) {
                        closeSidebar();
                    }
                });
            });
        }

        // Close sidebar or profile menu on "Escape" key press
        window.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                closeSidebar();
                closeProfileMenu();
            }
        });

        // Listen for screen size changes across the 960px breakpoint
        mobileSidebarBreakpoint.addEventListener("change", syncSidebarState);
    }

    function toggleProfileMenu() {
        if (!profileMenu) return;
        const isOpen = !profileMenu.hasAttribute("hidden");
        if (isOpen) {
            profileMenu.setAttribute("hidden", "");
        } else {
            profileMenu.removeAttribute("hidden");
        }
    }

    function closeProfileMenu() {
        if (!profileMenu) return;
        profileMenu.setAttribute("hidden", "");
    }

    function setupProfileMenuListeners() {
        // Toggle profile menu when profile button is clicked
        if (profileToggle) {
            profileToggle.addEventListener("click", (event) => {
                event.stopPropagation(); // Prevent window click event from immediately firing
                toggleProfileMenu();
            });
        }

        // Prevent closing when clicking inside the dropdown menu itself
        if (profileMenu) {
            profileMenu.addEventListener("click", (event) => {
                event.stopPropagation(); // Prevent window click event from immediately firing
            });
        }

        // Close profile menu when clicking anywhere else on the document
        document.addEventListener("click", () => {
            closeProfileMenu();
        });
    }

    const bannerStorageKey = "dashboardPromoSlides";
    const defaultPromoSlides = bladeDefaultPromoSlides || [
        { image: "../Image/promote1.jpg", alt: "School awards celebration" },
        { image: "../Image/promote2.jpg", alt: "Calendar and planning" },
        { image: "../Image/promote3.jpg", alt: "Staff recognition update" },
    ];
    const promoSlides = document.getElementById("promoSlides");
    const promoControls = document.getElementById("promoControls");
    const bannerModal = document.getElementById("bannerModal");
    const uploadedImages = document.getElementById("uploadedImages");
    const imageCount = document.getElementById("imageCount");
    let promoIndex = 0;
    let promoIntervalId = null;
    let uploadedFiles = [];
    let pendingUploads = [];
    const bannerMaxDimension = 1920;

    function getStoredPromoSlides() {
        try {
            const saved = localStorage.getItem(bannerStorageKey);
            return saved ? JSON.parse(saved) : defaultPromoSlides;
        } catch (error) {
            return defaultPromoSlides;
        }
    }

    function renderPromoBanner() {
        const slides = getStoredPromoSlides();
        promoSlides.innerHTML = slides.map((slide, index) => `
            <div class="promo-slide hidden [&.active]:block ${index === 0 ? "active" : ""}">
                <div class="${TW.promoSlideImage}"><img class="block w-full h-full object-cover object-center" src="${slide.image}" alt="${slide.alt}" /></div>
            </div>
        `).join("");
        promoControls.innerHTML = slides.map((slide, index) => `<button type="button" class="${TW.promoDot} ${index === 0 ? "active" : ""}" aria-label="Show banner ${index + 1}"></button>`).join("");
        promoControls.querySelectorAll("button").forEach((button, index) => {
            button.addEventListener("click", () => {
                promoIndex = index;
                updatePromoBanner();
                startPromoTimer(slides.length);
            });
        });
        startPromoTimer(slides.length);
    }

    function updatePromoBanner() {
        promoSlides.querySelectorAll(".promo-slide").forEach((slide, index) => slide.classList.toggle("active", index === promoIndex));
        promoControls.querySelectorAll("button").forEach((button, index) => button.classList.toggle("active", index === promoIndex));
    }

    function startPromoTimer(slideCount) {
        clearInterval(promoIntervalId);
        promoIntervalId = setInterval(() => {
            promoIndex = (promoIndex + 1) % slideCount;
            updatePromoBanner();
        }, 4500);
    }

    function renderUploadedImages() {
        uploadedImages.innerHTML = "";
        imageCount.textContent = `${uploadedFiles.length} image${uploadedFiles.length === 1 ? "" : "s"} selected`;
        uploadedFiles.forEach((slide, index) => {
            const preview = document.createElement("div");
            preview.className = TW.imagePreview;
            preview.innerHTML = `<img class="${TW.imagePreviewImg}" src="${slide.image}" alt="${slide.alt}" /><button class="${TW.removeImage}" type="button" aria-label="Remove image">&times;</button>`;
            preview.querySelector("button").addEventListener("click", () => {
                uploadedFiles.splice(index, 1);
                renderUploadedImages();
            });
            uploadedImages.appendChild(preview);
        });
    }

    function optimizeBannerImage(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => {
                const image = new Image();
                image.onload = () => {
                    const scale = Math.min(1, bannerMaxDimension / Math.max(image.naturalWidth, image.naturalHeight));
                    const canvas = document.createElement("canvas");
                    canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
                    canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
                    canvas.getContext("2d").drawImage(image, 0, 0, canvas.width, canvas.height);
                    const outputType = file.type === "image/png" ? "image/png" : "image/jpeg";
                    const imageData = canvas.toDataURL(outputType, outputType === "image/jpeg" ? 0.85 : undefined);
                    resolve({ image: imageData, alt: file.name });
                };
                image.onerror = reject;
                image.src = reader.result;
            };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    function getUploadedSlides(files) {
        return Promise.all(Array.from(files).map(optimizeBannerImage));
    }

    function setupBannerEditor() {
        const uploadArea = document.getElementById("uploadArea");
        const imageUpload = document.getElementById("imageUpload");
        document.getElementById("editBannerBtn").addEventListener("click", () => {
            uploadedFiles = getStoredPromoSlides().map((slide) => ({ ...slide }));
            pendingUploads = [];
            bannerModal.classList.remove("hidden");
            renderUploadedImages();
        });
        document.getElementById("closeBannerModal").addEventListener("click", () => bannerModal.classList.add("hidden"));
        document.getElementById("cancelBannerEdit").addEventListener("click", () => bannerModal.classList.add("hidden"));
        document.getElementById("resetToDefaults").addEventListener("click", () => {
            localStorage.removeItem(bannerStorageKey);
            renderPromoBanner();
            bannerModal.classList.add("hidden");
        });
        uploadArea.addEventListener("click", () => imageUpload.click());
        imageUpload.addEventListener("change", (event) => {
            const uploadPromise = getUploadedSlides(event.target.files).then((slides) => {
                uploadedFiles.push(...slides);
                renderUploadedImages();
            });
            pendingUploads.push(uploadPromise);
            event.target.value = "";
        });
        document.getElementById("saveBannerImages").addEventListener("click", async () => {
            await Promise.all(pendingUploads);
            pendingUploads = [];
            localStorage.setItem(bannerStorageKey, JSON.stringify(uploadedFiles));
            renderPromoBanner();
            bannerModal.classList.add("hidden");
        });
    }

    const calendarGrid = document.getElementById("calendarGrid");
    const calendarHeading = document.getElementById("calendarHeading");
    const taskStorageKey = "adminDashboardTasks";
    let tasks = JSON.parse(localStorage.getItem(taskStorageKey) || "[]");
    let currentDate = new Date();
    let selectedDate = new Date();

    function formatDay(date) { return date.toLocaleDateString("en-US", { year: "numeric", month: "2-digit", day: "2-digit" }); }
    function isToday(date) { const today = new Date(); return formatDay(date) === formatDay(today); }
    function saveTasks() { localStorage.setItem(taskStorageKey, JSON.stringify(tasks)); }

    function renderCalendar() {
        const month = currentDate.getMonth();
        const year = currentDate.getFullYear();
        const firstDay = new Date(year, month, 1).getDay();
        const totalDays = new Date(year, month + 1, 0).getDate();
        calendarHeading.textContent = selectedDate.toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" });
        calendarGrid.innerHTML = "";
        for (let index = 0; index < firstDay; index += 1) calendarGrid.insertAdjacentHTML("beforeend", '<div class="calendar-day empty aspect-square"></div>');
        for (let day = 1; day <= totalDays; day += 1) {
            const dayDate = new Date(year, month, day);
            const dayTasks = tasks.filter(task => task.date === formatDay(dayDate));
            const isSelected = formatDay(dayDate) === formatDay(selectedDate);
            const today = isToday(dayDate);
            const stateClass = isSelected ? TW.daySelected : today ? TW.dayToday : TW.dayNormal;
            const numClass = isSelected ? TW.numSelected : today ? TW.numToday : TW.numNormal;
            const count = dayTasks.length;
            const taskLabel = `${count} task${count === 1 ? "" : "s"}`;
            const cell = document.createElement("button");
            cell.type = "button";
            cell.className = `${TW.dayBase} ${stateClass}`;
            cell.setAttribute("aria-label", `${dayDate.toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" })}${count ? ", " + taskLabel : ""}`);
            // Phones: small count badge. sm and up: full "N tasks" text.
            cell.innerHTML = `<div class="calendar-day-content grid w-full gap-0.5 sm:gap-1"><span class="day-number text-sm font-extrabold leading-none sm:text-base ${numClass}">${day}</span>${count ? `<span class="sm:hidden justify-self-start inline-grid h-4 min-w-[1rem] place-items-center rounded-full bg-[#b22222] px-1 text-[0.6rem] font-bold leading-none text-white">${count}</span><span class="calendar-task-preview hidden overflow-hidden text-ellipsis whitespace-nowrap text-[0.72rem] leading-[1.1] text-[#6b7280] sm:block lg:text-[0.78rem]">${taskLabel}</span>` : ""}</div>`;
            cell.addEventListener("click", () => { selectedDate = dayDate; renderCalendar(); openTaskModal(dayDate); });
            calendarGrid.appendChild(cell);
        }
    }

    function openTaskModal(date) {
        const dateKey = formatDay(date);
        const modal = document.createElement("div");
        modal.className = TW.taskModal;
        modal.innerHTML = `<div class="${TW.taskModalContent}" role="dialog" aria-modal="true"><div class="${TW.taskModalHeader}"><h3 class="${TW.taskModalTitle}">Tasks for ${date.toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" })}</h3><button type="button" class="${TW.taskClose}" aria-label="Close">&times;</button></div><div class="${TW.taskModalBody}"><div class="${TW.taskInputRow}"><input class="${TW.taskInput}" type="text" placeholder="Add a new task" /><button type="button" class="${TW.redBtn} ${TW.taskAddBtn}">Add</button></div><div class="${TW.taskList}"></div></div></div>`;
        document.body.appendChild(modal);
        const list = modal.querySelector(".task-list");
        const renderTasks = () => {
            const dateTasks = tasks.filter(task => task.date === dateKey);
            list.innerHTML = dateTasks.length ? dateTasks.map(task => `<div class="${TW.taskItem}"><span class="${TW.taskTitle}">${task.title}</span><button type="button" class="${TW.clearBtn}">Remove</button></div>`).join("") : `<p class="${TW.emptyTask}">No tasks for this date. Add a new task below.</p>`;
            list.querySelectorAll("button").forEach((button, index) => button.addEventListener("click", () => { let removed = 0; tasks = tasks.filter(task => task.date !== dateKey || removed++ !== index); saveTasks(); renderTasks(); renderCalendar(); }));
        };
        modal.querySelector(".red-btn").addEventListener("click", () => { const input = modal.querySelector("input"); if (!input.value.trim()) return; tasks.push({ date: dateKey, title: input.value.trim() }); saveTasks(); input.value = ""; renderTasks(); renderCalendar(); });
        modal.querySelector(".close-btn").addEventListener("click", () => modal.remove());
        modal.addEventListener("click", event => { if (event.target === modal) modal.remove(); });
        renderTasks();
    }

    window.addEventListener("load", () => {
        setupSidebarEventListeners();
        setupProfileMenuListeners();
        renderPromoBanner();
        setupBannerEditor();
        renderCalendar();
        syncSidebarState();
    });

    window.addEventListener("hrms:storage", (event) => {
        const key = event.detail?.key;
        if (key !== bannerStorageKey && key !== null) return;

        renderPromoBanner();
        if (event.cancelable) event.preventDefault();
    });
</script>
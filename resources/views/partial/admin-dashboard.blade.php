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

<div class="admin-dashboard pb-12 ml-4">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- .main-content --}}
        <main class="main-content relative min-w-0 pt-[34px] pb-[42px] px-[clamp(20px,2.5vw,42px)] max-[960px]:pt-[82px]">

            {{-- .promo-banner --}}
            <section class="promo-banner relative mb-6 p-4 rounded-[28px] overflow-hidden max-[720px]:p-3 max-[720px]:rounded-3xl">
                <div class="promo-slides relative overflow-hidden rounded-[20px]" id="promoSlides">
                    @foreach (['promote1', 'promote2', 'promote3'] as $i => $img)
                        <div class="promo-slide hidden [&.active]:block {{ $i === 0 ? 'active' : '' }}">
                            <div class="promo-slide-image relative aspect-[16/6.4] min-h-[300px] overflow-hidden rounded-[20px] bg-white max-[720px]:min-h-[220px] max-[720px]:aspect-[4/3]">
                                <img src="{{ asset('Image/' . $img . '.jpg') }}" alt="{{ $img }}" class="block w-full h-full object-cover object-center" />
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="promo-controls absolute left-1/2 bottom-[26px] -translate-x-1/2 flex gap-2.5 z-[2]" id="promoControls"></div>
                <button id="editBannerBtn" class="edit-banner-btn absolute top-[26px] right-[26px] z-[3] grid place-items-center w-11 h-11 border-0 rounded-full bg-white/90 text-gray-900 shadow-[0_10px_30px_rgba(15,23,42,0.05)] cursor-pointer max-[720px]:top-4 max-[720px]:right-4 max-[720px]:w-10 max-[720px]:h-10" type="button">
                    <i class="fas fa-edit"></i>
                </button>
            </section>

            {{-- Banner modal --}}
            <div id="bannerModal" class="modal hidden fixed inset-0 z-[1000] flex items-center justify-center p-5 bg-black/50 backdrop-blur-lg [&.hidden]:hidden">
                <div class="modal-content banner-modal-content flex flex-col w-full max-w-[720px] max-h-[calc(100vh-32px)] overflow-hidden bg-white rounded-3xl shadow-[0_28px_70px_rgba(15,23,42,0.2)]">
                    <div class="modal-header flex justify-between items-center gap-4 pt-6 px-7 pb-5 border-b border-[#ecebea]">
                        <h3 class="m-0 text-gray-900 text-[1.17em] font-bold">Edit Banner Images</h3>
                        <button id="closeBannerModal" class="close-btn w-[38px] h-[38px] border-0 rounded-full bg-transparent text-[#b22222] text-[1.4rem] cursor-pointer transition-transform hover:text-[#dc2626] hover:scale-[1.08] active:scale-[0.94]">&times;</button>
                    </div>
                    <div class="modal-body flex-1 min-h-0 overflow-y-auto pt-6 px-7 pb-7">
                        <div class="upload-section grid gap-3">
                            <div class="upload-area relative overflow-hidden px-5 py-[34px] rounded-[22px] border-2 border-dashed border-gray-300 bg-gray-50 text-center cursor-pointer transition duration-200 hover:-translate-y-px hover:border-[#dc2626] hover:bg-[#fef2f2]" id="uploadArea">
                                <div class="upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt block mb-3 text-gray-400 text-[2.8rem]"></i>
                                    <p class="m-0 mb-2 text-gray-700 font-bold">Click to upload images or drag and drop</p>
                                    <span class="text-[#6b7280] text-[0.92rem]">Any image format supported by your browser</span>
                                </div>
                                <input type="file" id="imageUpload" class="absolute inset-0 opacity-0 cursor-pointer" multiple accept="image/png,image/jpeg" />
                            </div>
                            <div class="upload-info flex justify-end items-center gap-3">
                                <p id="imageCount" class="m-0 mr-auto text-[#6b7280] text-[0.92rem]">0 images selected</p>
                            </div>
                            <div class="uploaded-images grid grid-cols-[repeat(auto-fill,minmax(140px,1fr))] gap-3.5" id="uploadedImages"></div>
                        </div>
                        <div class="modal-footer flex items-center gap-3 justify-end pt-1.5 mt-1 max-[720px]:flex-col max-[720px]:items-stretch">
                            <button type="button" id="resetToDefaults" class="white-btn rounded-[14px] border border-gray-300 bg-white text-[#b22222] px-4 py-3 font-bold cursor-pointer transition duration-200 hover:-translate-y-px hover:border-[rgba(143,36,31,0.28)] active:translate-y-px active:scale-[0.98]">Reset to Defaults</button>
                            <button type="button" id="saveBannerImages" class="red-btn ml-auto max-[720px]:ml-0 rounded-[14px] border border-transparent bg-[#dc2626] text-white px-4 py-3 font-bold cursor-pointer transition duration-200 hover:-translate-y-px active:translate-y-px active:scale-[0.98]">Save Changes</button>
                            <button type="button" id="cancelBannerEdit" class="white-btn rounded-[14px] border border-gray-300 bg-white text-[#b22222] px-4 py-3 font-bold cursor-pointer transition duration-200 hover:-translate-y-px hover:border-[rgba(143,36,31,0.28)] active:translate-y-px active:scale-[0.98]">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Calendar --}}
            <div class="dashboard-content grid grid-cols-1 gap-6 items-start">
                <section class="calendar-panel p-7 bg-white max-[720px]:p-3.5">
                    <div class="calendar-header flex justify-between items-center gap-4 mb-3">
                        <h2 id="calendarHeading" class="m-0 text-gray-900 text-[1.8rem] font-extrabold">August 3, 2026</h2>
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
    // Prefer the Blade-generated asset URLs for the default banners
    const bladeDefaultPromoSlides = window.adminPromoDefaults;

    // ---------- Tailwind class strings used by the dynamically-rendered markup ----------
    const TW = {
        redBtn: "red-btn rounded-[14px] border border-transparent bg-[#dc2626] text-white px-4 py-3 font-bold cursor-pointer transition duration-200 hover:-translate-y-px active:translate-y-px active:scale-[0.98]",
        clearBtn: "clear-btn rounded-[14px] border border-gray-300 bg-white text-[#b22222] px-4 py-3 font-bold cursor-pointer transition duration-200 hover:-translate-y-px hover:border-[rgba(143,36,31,0.28)] active:translate-y-px active:scale-[0.98]",
        promoDot: "w-[11px] h-[11px] border-0 rounded-full bg-white cursor-pointer [&.active]:w-[15px] [&.active]:bg-[#b22222]",
        imagePreview: "image-preview relative overflow-hidden rounded-[18px] bg-gray-100 border-2 border-gray-200",
        imagePreviewImg: "block w-full h-32 object-cover",
        removeImage: "remove-image absolute top-2 right-2 w-[26px] h-[26px] border-0 rounded-full bg-red-500/90 text-white cursor-pointer",
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
                <div class="promo-slide-image relative aspect-[16/6.4] min-h-[300px] overflow-hidden rounded-[20px] bg-white max-[720px]:min-h-[220px] max-[720px]:aspect-[4/3]"><img class="block w-full h-full object-cover object-center" src="${slide.image}" alt="${slide.alt}" /></div>
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
            preview.innerHTML = `<img class="${TW.imagePreviewImg}" src="${slide.image}" alt="${slide.alt}" /><button class="${TW.removeImage}" type="button">&times;</button>`;
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
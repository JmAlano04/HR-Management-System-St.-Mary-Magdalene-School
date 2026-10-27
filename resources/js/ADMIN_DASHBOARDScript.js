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
const defaultPromoSlides = [
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
    <div class="promo-slide ${index === 0 ? "active" : ""}">
      <div class="promo-slide-image"><img src="${slide.image}" alt="${slide.alt}" /></div>
    </div>
  `).join("");
  promoControls.innerHTML = slides.map((slide, index) => `<button type="button" class="${index === 0 ? "active" : ""}" aria-label="Show banner ${index + 1}"></button>`).join("");
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
    preview.className = "image-preview";
    preview.innerHTML = `<img src="${slide.image}" alt="${slide.alt}" /><button class="remove-image" type="button">&times;</button>`;
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
  for (let index = 0; index < firstDay; index += 1) calendarGrid.insertAdjacentHTML("beforeend", '<div class="calendar-day empty"></div>');
  for (let day = 1; day <= totalDays; day += 1) {
    const dayDate = new Date(year, month, day);
    const dayTasks = tasks.filter(task => task.date === formatDay(dayDate));
    const cell = document.createElement("button");
    cell.type = "button";
    cell.className = `calendar-day${isToday(dayDate) ? " today" : ""}${formatDay(dayDate) === formatDay(selectedDate) ? " selected" : ""}`;
    cell.innerHTML = `<div class="calendar-day-content"><span class="day-number">${day}</span>${dayTasks.length ? `<span class="calendar-task-preview">${dayTasks.length} task${dayTasks.length === 1 ? "" : "s"}</span>` : ""}</div>`;
    cell.addEventListener("click", () => { selectedDate = dayDate; renderCalendar(); openTaskModal(dayDate); });
    calendarGrid.appendChild(cell);
  }
}

function openTaskModal(date) {
  const dateKey = formatDay(date);
  const modal = document.createElement("div");
  modal.className = "task-modal";
  modal.innerHTML = `<div class="task-modal-content"><div class="task-modal-header"><h3>Tasks for ${date.toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" })}</h3><button type="button" class="close-btn">&times;</button></div><div class="task-modal-body"><div class="task-input-row"><input type="text" placeholder="Add a new task" /><button type="button" class="red-btn">Add</button></div><div class="task-list"></div></div></div>`;
  document.body.appendChild(modal);
  const list = modal.querySelector(".task-list");
  const renderTasks = () => {
    const dateTasks = tasks.filter(task => task.date === dateKey);
    list.innerHTML = dateTasks.length ? dateTasks.map(task => `<div class="task-item"><span>${task.title}</span><button type="button" class="clear-btn">Remove</button></div>`).join("") : '<p class="empty-task">No tasks for this date. Add a new task below.</p>';
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
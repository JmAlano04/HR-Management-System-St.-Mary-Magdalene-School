
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

    {{-- Vite assets for this page --}}
    @vite(['resources/css/ADMIN_DASHBOARDStyle.css', 'resources/js/ADMIN_DASHBOARDScript.js'])

    {{-- Defaults must be defined BEFORE the script runs (Vite scripts are deferred, so this is fine) --}}
    <script>
        window.adminPromoDefaults = [
            { image: "{{ asset('Image/promote1.jpg') }}", alt: "School awards celebration" },
            { image: "{{ asset('Image/promote2.jpg') }}", alt: "Calendar and planning" },
            { image: "{{ asset('Image/promote3.jpg') }}", alt: "Staff recognition update" },
        ];
    </script>

    <div class="admin-dashboard pb-12 ml-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

           <main class="main-content">
      
      <section class="promo-banner">
        <div class="promo-slides" id="promoSlides">
          <div class="promo-slide active">
            <div class="promo-slide-image">
              <img src="../Image/promote1.jpg" alt="promote1" />
            </div>
          </div>
          <div class="promo-slide">
            <div class="promo-slide-image">
              <img src="../Image/promote2.jpg" alt="promote2" />
            </div>
          </div>
          <div class="promo-slide">
            <div class="promo-slide-image">
              <img src="../Image/promote3.jpg" alt="promote3" />
            </div>
          </div>
        </div>
        <div class="promo-controls" id="promoControls"></div>
        <button id="editBannerBtn" class="edit-banner-btn" type="button">
          <i class="fas fa-edit"></i>
        </button>
      </section>

      <div id="bannerModal" class="modal hidden">
        <div class="modal-content banner-modal-content">
          <div class="modal-header">
            <h3>Edit Banner Images</h3>
            <button id="closeBannerModal" class="close-btn">&times;</button>
          </div>
          <div class="modal-body">
            <div class="upload-section">
              <div class="upload-area" id="uploadArea">
                <div class="upload-placeholder">
                  <i class="fas fa-cloud-upload-alt"></i>
                  <p>Click to upload images or drag and drop</p>
                  <span>Any image format supported by your browser</span>
                </div>
                <input type="file" id="imageUpload" multiple accept="image/png,image/jpeg" />
              </div>
              <div class="upload-info">
                <p id="imageCount">0 images selected</p>
              </div>
              <div class="uploaded-images" id="uploadedImages"></div>
            </div>
            <div class="modal-footer">
              <button type="button" id="resetToDefaults" class="white-btn">Reset to Defaults</button>
              <button type="button" id="saveBannerImages" class="red-btn">Save Changes</button>
              <button type="button" id="cancelBannerEdit" class="white-btn">Cancel</button>
            </div>
          </div>
        </div>
      </div>

      <div class="dashboard-content">
        <section class="calendar-panel">
          <div class="calendar-header">
            <h2 id="calendarHeading">August 3, 2026</h2>
          </div>

          <div class="calendar-widget">
            <div class="calendar-weekdays">
              <span>Sun</span>
              <span>Mon</span>
              <span>Tue</span>
              <span>Wed</span>
              <span>Thu</span>
              <span>Fri</span>
              <span>Sat</span>
            </div>
            <div class="calendar-grid" id="calendarGrid"></div>
          </div>
        </section>
      </div>
    </main>
    </div>
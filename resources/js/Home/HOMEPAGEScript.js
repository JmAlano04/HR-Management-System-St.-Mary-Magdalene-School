/* ==========================================================
   resources/js/Home/HOMEPAGEScript.js
   HR Management System - Homepage Script (fixed)
   ========================================================== */

/* ==========================================================
   MODAL FUNCTIONS
   ========================================================== */

// ----- Login modal -----
function openLoginModal() {
    const modal = document.getElementById('login-modal');

    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeLoginModal() {
    const modal = document.getElementById('login-modal');

    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ----- Apply modal -----
function openApplyModal() {
    const modal = document.getElementById('apply-modal');
    if (!modal) return;

    modal.hidden = false;
    document.body.style.overflow = 'hidden';
}

function closeApplyModal() {
    const modal = document.getElementById('apply-modal');
    if (!modal) return;

    modal.hidden = true;
    document.body.style.overflow = '';
}

// ----- Signup modal -----
function openSignupModal() {
    const modal = document.getElementById('signup-modal');
    if (!modal) return;

    modal.hidden = false;
    document.body.style.overflow = 'hidden';

    const requirements = document.getElementById('password-requirements');

    if (requirements) {
        requirements.style.display = 'block';
    }

    // "autofocus" does not work while the modal is hidden,
    // so focus the first field manually.
    const nameInput = document.getElementById('signup-name');

    if (nameInput) {
        setTimeout(() => nameInput.focus(), 50);
    }
}

function closeSignupModal() {
    const modal = document.getElementById('signup-modal');
    if (!modal) return;

    modal.hidden = true;
    document.body.style.overflow = '';
}

// ----- Apply login modal -----
function openApplyLoginModal() {
    const modal = document.getElementById('apply-login-modal');

    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeApplyLoginModal() {
    const modal = document.getElementById('apply-login-modal');

    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ----- Facilities modal -----
function openFacilitiesModal(src, alt) {
    const modal = document.getElementById('FacilitiesModal');
    const img = document.getElementById('FacilitiesModal-img');

    if (!modal || !img) return;

    img.src = src;
    img.alt = alt || '';

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeFacilitiesModal() {
    const modal = document.getElementById('FacilitiesModal');
    if (!modal) return;

    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Expose functions for inline onclick attributes
window.openLoginModal = openLoginModal;
window.closeLoginModal = closeLoginModal;

window.openApplyModal = openApplyModal;
window.closeApplyModal = closeApplyModal;

window.openSignupModal = openSignupModal;
window.closeSignupModal = closeSignupModal;

window.openApplyLoginModal = openApplyLoginModal;
window.closeApplyLoginModal = closeApplyLoginModal;

window.openFacilitiesModal = openFacilitiesModal;
window.closeFacilitiesModal = closeFacilitiesModal;


/* ==========================================================
   PAGE SETUP
   ========================================================== */

document.addEventListener('DOMContentLoaded', function () {

    // ---------- Element references ----------
    const menuToggle = document.querySelector('.menu-toggle');
    const topNav = document.getElementById('top-nav');

    const loginBtn = document.getElementById('login-btn');
    const applyBtn = document.getElementById('apply-btn');

    const applyModal = document.getElementById('apply-modal');
    const signupModal = document.getElementById('signup-modal');

    const signupOpenBtn = document.getElementById('signup-open-btn');
    const signupCloseBtn = signupModal?.querySelector('.modal-close');
    const applyCloseBtn = applyModal?.querySelector('.modal-close');

    const signupForm = document.getElementById('signup-form');

    const applyLoginBtn = document.getElementById('apply-login-btn');
    const applyLoginForm = document.getElementById('apply-login-form');
    const applyLoginSignupLink =
        document.getElementById('apply-login-signup-link');


    /* ======================================================
       APPLY MODAL
       ====================================================== */

    if (applyBtn && applyModal) {
        applyBtn.addEventListener('click', function (event) {
            event.preventDefault();
            openApplyModal();
        });
    }

    if (applyCloseBtn) {
        applyCloseBtn.addEventListener('click', closeApplyModal);
    }

    if (applyModal) {
        applyModal.addEventListener('click', function (event) {
            if (event.target === applyModal) {
                closeApplyModal();
            }
        });
    }

    if (signupOpenBtn && signupModal) {
        signupOpenBtn.addEventListener('click', function (event) {
            event.preventDefault();

            closeApplyModal();
            openSignupModal();
        });
    }

    if (applyLoginBtn) {
        applyLoginBtn.addEventListener('click', function (event) {
            event.preventDefault();

            closeApplyModal();
            openApplyLoginModal();
        });
    }

    if (applyLoginSignupLink) {
        applyLoginSignupLink.addEventListener('click', function (event) {
            event.preventDefault();

            closeApplyLoginModal();
            openSignupModal();
        });
    }


    /* ======================================================
       SIGNUP MODAL CLOSE
       ====================================================== */

    if (signupCloseBtn) {
        signupCloseBtn.addEventListener('click', closeSignupModal);
    }

    if (signupModal) {
        signupModal.addEventListener('click', function (event) {
            if (event.target === signupModal) {
                closeSignupModal();
            }
        });
    }


    /* ======================================================
       REOPEN SIGNUP MODAL AFTER SERVER VALIDATION ERRORS
       The form has data-has-errors="true" when Laravel
       returned validation errors (see signup-form.blade.php).
       ====================================================== */

    if (signupForm && signupForm.dataset.hasErrors === 'true') {
        openSignupModal();
    }


    /* ======================================================
       PASSWORD VISIBILITY TOGGLES
       ====================================================== */

    function updatePasswordIcon(toggle, isVisible) {
        if (isVisible) {
            // Eye open (password is visible)
            toggle.innerHTML = `
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <circle
                        cx="12"
                        cy="12"
                        r="3"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    />
                </svg>
            `;
        } else {
            // Eye with slash (password is hidden)
            toggle.innerHTML = `
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M17 17l-10-10"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            `;
        }
    }

    document.querySelectorAll('.password-toggle').forEach(toggle => {
        // Set the correct initial icon (password starts hidden)
        updatePasswordIcon(toggle, false);

        toggle.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);

            if (!input) return;

            const shouldShow = input.type === 'password';

            input.type = shouldShow ? 'text' : 'password';

            this.setAttribute('aria-pressed', String(shouldShow));
            this.setAttribute(
                'aria-label',
                shouldShow ? 'Hide password' : 'Show password'
            );

            updatePasswordIcon(this, shouldShow);
        });
    });


    /* ======================================================
       SIGNUP PASSWORD REQUIREMENTS + CONFIRM PASSWORD
       Rules must match Password::defaults() in
       AppServiceProvider (min 12, mixed case, numbers, symbols).
       ====================================================== */

    const signupPassword =
        document.getElementById('signup-password');

    const signupConfirmPassword =
        document.getElementById('signup-confirm-password');

    const signupErrorMessage =
        document.getElementById('signup-error-message');

    const passwordRequirementsContainer =
        document.getElementById('password-requirements');

    const passwordCount =
        document.getElementById('password-count');

    const signupSubmitButton = signupForm
        ? signupForm.querySelector('button[type="submit"]')
        : null;

    const passwordRequirements = [
        { key: 'length',  test: value => value.length >= 12 },
        { key: 'capital', test: value => /[A-Z]/.test(value) },
        { key: 'number',  test: value => /[0-9]/.test(value) },
        { key: 'symbol',  test: value => /[^A-Za-z0-9]/.test(value) }
    ];

    function allRequirementsMet(value) {
        return passwordRequirements.every(rule => rule.test(value));
    }

    function updatePasswordRequirements() {
        if (!signupPassword) return false;

        const value = signupPassword.value;
        let metCount = 0;

        passwordRequirements.forEach(rule => {
            const requirement = passwordRequirementsContainer?.querySelector(
                `.requirement[data-requirement="${rule.key}"]`
            );

            const met = rule.test(value);

            if (met) {
                metCount++;
            }

            if (requirement) {
                requirement.classList.toggle('met', met);
            }
        });

        if (passwordCount) {
            passwordCount.textContent =
                `(${metCount}/${passwordRequirements.length})`;
        }

        return metCount === passwordRequirements.length;
    }

    function checkPasswords() {
        if (!signupPassword || !signupConfirmPassword) {
            return false;
        }

        const password = signupPassword.value;
        const confirmation = signupConfirmPassword.value;

        const hasConfirmation = confirmation.length > 0;
        const matches = password === confirmation;

        if (signupErrorMessage) {
            const showError = hasConfirmation && !matches;

            signupErrorMessage.hidden = !showError;
            signupErrorMessage.style.display = showError ? 'block' : 'none';
        }

        signupConfirmPassword.setCustomValidity(
            hasConfirmation && !matches
                ? 'Passwords do not match.'
                : ''
        );

        return hasConfirmation && matches;
    }

    function validateSignup() {
        const requirementsMet = updatePasswordRequirements();
        const passwordsMatch = checkPasswords();

        return requirementsMet && passwordsMatch;
    }

    function updateSignupButton() {
        if (!signupSubmitButton) return;

        const requirementsMet = updatePasswordRequirements();

        signupSubmitButton.disabled = !requirementsMet;
    }

    if (signupPassword) {
        signupPassword.addEventListener('input', function () {
            updateSignupButton();
            checkPasswords();
        });
    }

    if (signupConfirmPassword) {
        signupConfirmPassword.addEventListener('input', function () {
            checkPasswords();
        });
    }

    // A valid registration must submit to Laravel's register route.
    if (signupForm) {
        signupForm.addEventListener('submit', function (event) {
            const nameInput = document.getElementById('signup-name');
            const emailInput = document.getElementById('signup-email');

            // Check the basic fields first.
            if (
                !nameInput?.value.trim() ||
                !emailInput?.value.trim() ||
                !signupPassword?.value ||
                !signupConfirmPassword?.value
            ) {
                event.preventDefault();

                if (!nameInput?.value.trim()) {
                    nameInput?.focus();
                } else if (!emailInput?.value.trim()) {
                    emailInput?.focus();
                } else if (!signupPassword?.value) {
                    signupPassword?.focus();
                } else {
                    signupConfirmPassword?.focus();
                }

                return;
            }

            if (!validateSignup()) {
                event.preventDefault();

                if (!allRequirementsMet(signupPassword.value)) {
                    signupPassword.focus();
                } else {
                    signupConfirmPassword.focus();
                    signupConfirmPassword.reportValidity();
                }

                return;
            }

            // Valid: allow native submission (POST to route('register')).
        });
    }

    // Initial state
    updateSignupButton();
    checkPasswords();


    /* ======================================================
       APPLY LOGIN FORM
       Submits natively to Laravel. Only blocks empty fields.
       Make sure the form has method="POST", @csrf and
       action="{{ route('login') }}".
       ====================================================== */

    if (applyLoginForm) {
        applyLoginForm.addEventListener('submit', function (event) {
            const email =
                document.getElementById('apply-email')?.value.trim();

            const password =
                document.getElementById('apply-password')?.value;

            if (!email || !password) {
                event.preventDefault();
                alert('Please enter your email and password.');
            }
        });
    }


    /* ======================================================
       MAIN LOGIN MODAL
       Uses real Laravel authentication. The role-based
       redirect (admin / hr / accounting / employee) must be
       done on the server after login. See the controller
       example in the notes.
       ====================================================== */

    if (loginBtn) {
        loginBtn.addEventListener('click', function (event) {
            event.preventDefault();
            openLoginModal();
        });
    }

    const loginForm = document.getElementById('login-form');

    if (loginForm) {
        loginForm.addEventListener('submit', function (event) {
            const email = document.getElementById('email')?.value.trim();
            const password = document.getElementById('password')?.value;

            if (!email || !password) {
                event.preventDefault();
                alert('Please enter email and password.');
            }
            // Otherwise let the form POST to route('login').
        });
    }


    /* ======================================================
       CLOSE MODALS WITH ESCAPE
       ====================================================== */

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeLoginModal();
            closeApplyLoginModal();
            closeFacilitiesModal();
            closeSignupModal();
            closeApplyModal();
        }
    });


    /* ======================================================
       NAVIGATION MENU
       ====================================================== */

    if (menuToggle && topNav) {
        menuToggle.addEventListener('click', function () {
            const isOpen = topNav.classList.toggle('open');

            menuToggle.classList.toggle('active', isOpen);
            menuToggle.setAttribute('aria-expanded', String(isOpen));
        });

        topNav.querySelectorAll('a, button').forEach(link => {
            link.addEventListener('click', function () {
                topNav.classList.remove('open');
                menuToggle.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            });
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                topNav.classList.remove('open');
                menuToggle.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }


    /* ======================================================
       HOMEPAGE SLIDER
       ====================================================== */

    const slides = Array.from(document.querySelectorAll('.slide'));
    const dotsContainer = document.getElementById('dots');
    const banner = document.querySelector('.banner');

    // Everything above must still work if no slider exists.
    if (!slides.length || !dotsContainer || !banner) {
        return;
    }

    let currentSlide = 0;
    let sliderTimer = null;

    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });

        Array.from(dotsContainer.children).forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });

        currentSlide = index;
    }

    function nextSlide() {
        showSlide((currentSlide + 1) % slides.length);
    }

    function restartSliderTimer() {
        clearInterval(sliderTimer);
        sliderTimer = setInterval(nextSlide, 4000);
    }

    // Prevent duplicate dots if the script is initialized again.
    dotsContainer.innerHTML = '';

    slides.forEach((slide, index) => {
        const dot = document.createElement('button');

        dot.type = 'button';
        dot.setAttribute('aria-label', `Show slide ${index + 1}`);

        dot.addEventListener('click', function () {
            showSlide(index);
            restartSliderTimer();
        });

        if (index === 0) {
            dot.classList.add('active');
        }

        dotsContainer.appendChild(dot);
    });

    showSlide(0);

    banner.addEventListener('mouseenter', () => {
        clearInterval(sliderTimer);
    });

    banner.addEventListener('mouseleave', restartSliderTimer);

    sliderTimer = setInterval(nextSlide, 4000);
});
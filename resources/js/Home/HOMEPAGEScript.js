// resources/js/Home/HOMEPAGEScript.js

/* ==========================================================
   MODAL FUNCTIONS (top level, then exposed to window so the
   inline onclick="..." attributes in the Blade file work)
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

// ----- Apply modal (uses the hidden attribute) -----
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

// ----- Sign up modal (uses the hidden attribute) -----
function openSignupModal() {
    const modal = document.getElementById('signup-modal');
    if (!modal) return;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    const pr = document.getElementById('password-requirements');
    if (pr) pr.style.display = 'block';
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
    img.alt = alt;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeFacilitiesModal() {
    const modal = document.getElementById('FacilitiesModal');
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Expose to the global scope (needed because Vite loads this as a module)
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
   DEMO CREDENTIALS
   ========================================================== */
const demoCredentials = {
    admin: { email: 'admin@stmary.edu', password: 'admin123' },
    hr: { email: 'hr@stmary.edu', password: 'hr123' },
    accounting: { email: 'accounting@stmary.edu', password: 'acct123' },
    employee: { email: 'john.doe@stmary.edu', password: 'employee123' }
};

/* ==========================================================
   PAGE SETUP
   ========================================================== */
document.addEventListener('DOMContentLoaded', function () {
    console.log('HOMEPAGE script loaded');

    // ---------- Element references ----------
    const menuToggle = document.querySelector('.menu-toggle');
    const topNav = document.getElementById('top-nav');
    const loginBtn = document.getElementById('login-btn');
    const applyBtn = document.getElementById('apply-btn');

    const applyModal = document.getElementById('apply-modal');
    const signupModal = document.getElementById('signup-modal');
    const signupOpenBtn = document.getElementById('signup-open-btn');
    const signupCloseBtn = signupModal?.querySelector('.modal-close');
    const closeModalBtn = applyModal?.querySelector('.modal-close');
    const signupForm = document.getElementById('signup-form');

    const applyLoginBtn = document.getElementById('apply-login-btn');
    const applyLoginForm = document.getElementById('apply-login-form');
    const applyLoginSignupLink = document.getElementById('apply-login-signup-link');

    // ---------- Apply modal ----------
    if (applyBtn && applyModal) {
        applyBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openApplyModal();
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeApplyModal);
    }

    if (applyModal) {
        applyModal.addEventListener('click', function (e) {
            if (e.target === applyModal) closeApplyModal();
        });
    }

    if (signupOpenBtn && signupModal) {
        signupOpenBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeApplyModal();
            openSignupModal();
        });
    }

    if (applyLoginBtn) {
        applyLoginBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeApplyModal();
            openApplyLoginModal();
        });
    }

    if (applyLoginSignupLink) {
        applyLoginSignupLink.addEventListener('click', function (e) {
            e.preventDefault();
            closeApplyLoginModal();
            openSignupModal();
        });
    }

    // ---------- Sign up modal close ----------
    if (signupCloseBtn) {
        signupCloseBtn.addEventListener('click', closeSignupModal);
    }

    if (signupModal) {
        signupModal.addEventListener('click', function (e) {
            if (e.target === signupModal) closeSignupModal();
        });
    }

    // ---------- Password visibility toggles ----------
    function updatePasswordIcon(toggle, isVisible) {
        if (isVisible) {
            toggle.innerHTML = `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>`;
        } else {
            toggle.innerHTML = `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 17l-10-10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
        }
    }

    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function () {
            const input = document.getElementById(this.getAttribute('data-target'));
            if (!input) return;
            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            this.setAttribute('aria-pressed', String(shouldShow));
            this.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            updatePasswordIcon(this, shouldShow);
        });
    });

    // ---------- Sign up: password requirements + match check ----------
    const signupPassword = document.getElementById('signup-password');
    const signupConfirmPassword = document.getElementById('signup-confirm-password');
    const signupErrorMessage = document.getElementById('signup-error-message');
    const passwordRequirementsContainer = document.getElementById('password-requirements');
    const passwordCount = document.getElementById('password-count');
    const signupSubmitButton = signupForm ? signupForm.querySelector('button[type="submit"]') : null;
    if (signupSubmitButton) signupSubmitButton.disabled = true;

    const passwordRequirements = [
        { key: 'length', test: p => p.length >= 12 },
        { key: 'capital', test: p => /[A-Z]/.test(p) },
        { key: 'number', test: p => /\d/.test(p) },
        { key: 'symbol', test: p => /[^A-Za-z0-9]/.test(p) }
    ];

    function updatePasswordRequirements(pass) {
        if (!passwordRequirementsContainer) return;
        let metCount = 0;
        passwordRequirements.forEach(r => {
            const el = passwordRequirementsContainer.querySelector(`.requirement[data-requirement="${r.key}"]`);
            if (!el) return;
            const met = r.test(pass);
            el.classList.toggle('met', met);
            if (met) metCount++;
        });
        if (passwordCount) passwordCount.textContent = `(${metCount}/${passwordRequirements.length})`;
        if (signupSubmitButton) signupSubmitButton.disabled = metCount !== passwordRequirements.length;
    }

    function allRequirementsMet(pass) {
        return passwordRequirements.every(r => r.test(pass));
    }

    function checkPasswords() {
        if (!signupPassword || !signupConfirmPassword || !signupErrorMessage) return true;

        const pass = signupPassword.value.trim();
        const confirmPass = signupConfirmPassword.value.trim();

        updatePasswordRequirements(pass);

        if (confirmPass.length > 0) {
            if (pass !== confirmPass) {
                signupErrorMessage.style.display = 'block';
                return false;
            }
            signupErrorMessage.style.display = 'none';
            return true;
        }
        signupErrorMessage.style.display = 'none';
        return true;
    }

    if (signupPassword) signupPassword.addEventListener('input', checkPasswords);
    if (signupConfirmPassword) signupConfirmPassword.addEventListener('input', checkPasswords);

    if (signupForm) {
        signupForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const name = document.getElementById('signup-name').value.trim();
            const email = document.getElementById('signup-email').value.trim();
            const password = signupPassword ? signupPassword.value.trim() : '';
            const confirmPassword = signupConfirmPassword ? signupConfirmPassword.value.trim() : '';

            if (!name || !email || !password || !confirmPassword) {
                alert('Please fill in all fields.');
                return;
            }

            if (!allRequirementsMet(password)) {
                alert('Password does not meet requirements.');
                if (signupPassword) signupPassword.focus();
                return;
            }

            if (!checkPasswords()) {
                if (signupConfirmPassword) signupConfirmPassword.focus();
                return;
            }

            alert('Account created successfully!');
            signupForm.reset();
            if (signupErrorMessage) signupErrorMessage.style.display = 'none';
            if (passwordRequirementsContainer) {
                passwordRequirementsContainer.querySelectorAll('.requirement').forEach(el => el.classList.remove('met'));
            }
            if (passwordCount) passwordCount.textContent = `(0/${passwordRequirements.length})`;
            if (signupSubmitButton) signupSubmitButton.disabled = true;
            closeSignupModal();
        });
    }

    // ---------- Apply login form ----------
    if (applyLoginForm) {
        applyLoginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const email = document.getElementById('apply-email').value.trim();
            const password = document.getElementById('apply-password').value.trim();

            if (!email || !password) {
                alert('Please enter your email and password.');
                return;
            }

            alert('Login successful!');
            closeApplyLoginModal();
        });
    }

    // ---------- Main login modal ----------
    if (loginBtn) {
        loginBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openLoginModal();
        });
    }

    const loginTabs = document.querySelectorAll('.login-tab');
    const credentialsText = document.getElementById('credentials-text');
    const loginForm = document.getElementById('login-form');
    let currentRole = 'admin';

    function showCredentials(role) {
        const creds = demoCredentials[role];
        if (!creds || !credentialsText) return;
        const roleLabel = role.charAt(0).toUpperCase() + role.slice(1);
        credentialsText.innerHTML = `<strong>${roleLabel}:</strong> ${creds.email} / ${creds.password}`;
    }

    loginTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            currentRole = this.dataset.role;
            loginTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            showCredentials(currentRole);
        });
    });

    if (loginTabs.length > 0) showCredentials('admin');

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!email || !password) {
                alert('Please enter email and password');
                return;
            }

            const selected = demoCredentials[currentRole];
            if (!selected || email !== selected.email || password !== selected.password) {
                alert('Invalid email or password for the selected role');
                return;
            }

            const dashboardRoutes = {
                admin: '../Admin_Dashboard/Admin_Dashboard.html',
                hr: '../HR_DASHBOARD/HR_DASHBOARD.html',
                accounting: '../ACCOUNTING_DASHBOARD/ACC_DASHBOARD.html',
                employee: '../EMPLOYEE_DASHBOARD/EMPLOYEE_DASHBOARD.html'
            };

            const dashboardURL = dashboardRoutes[currentRole];

            if (dashboardURL) {
                closeLoginModal();
                sessionStorage.setItem('userRole', currentRole);
                sessionStorage.setItem('userEmail', email);
                window.location.href = dashboardURL;
            } else {
                alert('Invalid role selected');
            }
        });
    }

    // ---------- Close every modal with Escape ----------
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeLoginModal();
            closeApplyLoginModal();
            closeFacilitiesModal();
            closeSignupModal();
            closeApplyModal();
        }
    });

    // ---------- Navigation menu toggle ----------
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

    // ---------- Slider (kept last so an early return can't skip anything above) ----------
    const slides = Array.from(document.querySelectorAll('.slide'));
    const dotsContainer = document.getElementById('dots');
    const banner = document.querySelector('.banner');
    if (!slides.length || !dotsContainer || !banner) return;

    let current = 0;
    let timer = null;

    function show(index) {
        slides.forEach((s, i) => s.classList.toggle('active', i === index));
        Array.from(dotsContainer.children).forEach((d, i) => d.classList.toggle('active', i === index));
        current = index;
    }

    function next() {
        show((current + 1) % slides.length);
    }

    function restartTimer() {
        clearInterval(timer);
        timer = setInterval(next, 4000);
    }

    slides.forEach((s, i) => {
        const b = document.createElement('button');
        b.addEventListener('click', () => { show(i); restartTimer(); });
        if (i === 0) b.classList.add('active');
        dotsContainer.appendChild(b);
    });

    banner.addEventListener('mouseenter', () => clearInterval(timer));
    banner.addEventListener('mouseleave', restartTimer);

    timer = setInterval(next, 4000);
});
// HOMEPAGE/HOMEPAGEScript.js
document.addEventListener('DOMContentLoaded', function(){
    console.log('HOMEPAGE script loaded');
    const slides = Array.from(document.querySelectorAll('.slide'));
    const dotsContainer = document.getElementById('dots');
    const menuToggle = document.querySelector('.menu-toggle');
    const topNav = document.getElementById('top-nav');
    const loginBtn = document.getElementById('login-btn');
    const applyBtn = document.getElementById('apply-btn');
    const applyModal = document.getElementById('apply-modal');
    const signupModal = document.getElementById('signup-modal');
    const signupOpenBtn = document.getElementById('signup-open-btn');
    const signupCloseBtn = signupModal?.querySelector('.modal-close');
    const signupForm = document.getElementById('signup-form');
    const closeModalBtn = applyModal?.querySelector('.modal-close');
    let current = 0;
    let timer = null;
    let signupHandlersAttached = false;

    // Apply modal functionality
    function openApplyModal(){
        if(!applyModal) return;
        applyModal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    // Close Apply modal
    function closeApplyModal(){
        if(!applyModal) return;
        applyModal.hidden = true;
        document.body.style.overflow = '';
    }

    function openSignupModal(){
        if(!signupModal) return;
        signupModal.hidden = false;
        document.body.style.overflow = 'hidden';
        const sp = document.getElementById('signup-password');
        const scp = document.getElementById('signup-confirm-password');
        const pr = document.getElementById('password-requirements');
        if(pr) pr.style.display = 'block';

        // Attach handlers once when modal opens
        if(!signupHandlersAttached){
            if(sp){
                sp.addEventListener('input', function onSpInput(){
                    updatePasswordRequirements(sp.value || '');
                    checkPasswords();
                });
                // run initial update in case value is autocompleted
                updatePasswordRequirements(sp.value || '');
            }
            if(scp){
                scp.addEventListener('input', checkPasswords);
            }
            signupHandlersAttached = true;
        }
    }

    function closeSignupModal(){
        if(!signupModal) return;
        signupModal.hidden = true;
        document.body.style.overflow = '';
    }

    // FIX: these functions live inside this DOMContentLoaded block, so the
    // Escape handler further down (a different block) could not see them and
    // threw a ReferenceError. Exposing them on window makes them reachable
    // from the Escape key and from inline onclick="..." attributes.
    window.openApplyModal = openApplyModal;
    window.closeApplyModal = closeApplyModal;
    window.openSignupModal = openSignupModal;
    window.closeSignupModal = closeSignupModal;

    // Event listeners for Apply modal
    if(applyBtn && applyModal){
        applyBtn.addEventListener('click', function(e){
            e.preventDefault();
            openApplyModal();
        });
    }
    
    // Close modal when clicking outside the modal content
    if(closeModalBtn){
        closeModalBtn.addEventListener('click', closeApplyModal);
    }

    if(applyModal){
        applyModal.addEventListener('click', function(e){
            if(e.target === applyModal) closeApplyModal();
        });
    }

    const applyLoginBtn = document.getElementById('apply-login-btn');
    const applyLoginModal = document.getElementById('apply-login-modal');
    const applyLoginForm = document.getElementById('apply-login-form');
    const applyLoginSignupLink = document.getElementById('apply-login-signup-link');

    if(signupOpenBtn && signupModal){
        signupOpenBtn.addEventListener('click', function(e){
            e.preventDefault();
            closeApplyModal();
            openSignupModal();
        });
    }

    if(applyLoginBtn && applyLoginModal){
        applyLoginBtn.addEventListener('click', function(e){
            e.preventDefault();
            closeApplyModal();
            openApplyLoginModal();
        });
    }

    if(applyLoginSignupLink){
        applyLoginSignupLink.addEventListener('click', function(e){
            e.preventDefault();
            closeApplyLoginModal();
            openSignupModal();
        });
    }

    if(signupCloseBtn){
        signupCloseBtn.addEventListener('click', closeSignupModal);
    }

    if(signupModal){
        signupModal.addEventListener('click', function(e){
            if(e.target === signupModal) closeSignupModal();
        });
    }

    // Password visibility toggles
    function updatePasswordIcon(toggle, isVisible) {
        if(isVisible) {
            toggle.innerHTML = `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>`;
        } else {
            toggle.innerHTML = `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 17l-10-10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
        }
    }

    Array.from(document.querySelectorAll('.password-toggle')).forEach(toggle => {
        toggle.addEventListener('click', function(){
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if(!input) return;
            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            this.setAttribute('aria-pressed', String(shouldShow));
            this.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            updatePasswordIcon(this, shouldShow);
        });
    });

    // Sign-Up Password Match Real-time Validation + Requirements
    const signupPassword = document.getElementById('signup-password');
    const signupConfirmPassword = document.getElementById('signup-confirm-password');
    const signupErrorMessage = document.getElementById('signup-error-message');
    const passwordRequirementsContainer = document.getElementById('password-requirements');
    const passwordCount = document.getElementById('password-count');
    const signupSubmitButton = signupForm ? signupForm.querySelector('button[type="submit"]') : null;
    if(signupSubmitButton) signupSubmitButton.disabled = true;

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
            if (met) { el.classList.add('met'); metCount++; } else { el.classList.remove('met'); }
            // eslint-disable-next-line no-console
            console.log(`requirement ${r.key}: ${met}`);
        });
        if(passwordCount) passwordCount.textContent = `(${metCount}/${passwordRequirements.length})`;
        if(signupSubmitButton) signupSubmitButton.disabled = metCount !== passwordRequirements.length;
    }

    function allRequirementsMet(pass) {
        return passwordRequirements.every(r => r.test(pass));
    }

    function checkPasswords() {
        if (!signupPassword || !signupConfirmPassword || !signupErrorMessage) return true;

        const pass = signupPassword.value.trim();
        const confirmPass = signupConfirmPassword.value.trim();

        // update requirements indicators
        updatePasswordRequirements(pass);

        if (confirmPass.length > 0) {
            if (pass !== confirmPass) {
                signupErrorMessage.style.display = 'block';
                return false;
            } else {
                signupErrorMessage.style.display = 'none';
                return true;
            }
        }
        signupErrorMessage.style.display = 'none';
        return true;
    }

    if (signupPassword && signupConfirmPassword) {
        signupPassword.addEventListener('input', checkPasswords);
        signupPassword.addEventListener('input', function(){ updatePasswordRequirements(signupPassword.value); });
        signupConfirmPassword.addEventListener('input', checkPasswords);
    }

    // if(signupForm){
    //     signupForm.addEventListener('submit', function(e){
    //         e.preventDefault();
    //         const name = document.getElementById('signup-name').value.trim();
    //         const email = document.getElementById('signup-email').value.trim();
    //         const password = signupPassword ? signupPassword.value.trim() : '';
    //         const confirmPassword = signupConfirmPassword ? signupConfirmPassword.value.trim() : '';

    //         if(!name || !email || !password || !confirmPassword){
    //             alert('Please fill in all fields.');
    //             return;
    //         }

    //         // Ensure password meets requirements
    //         if(!allRequirementsMet(password)){
    //             alert('Password does not meet requirements.');
    //             if(signupPassword) signupPassword.focus();
    //             return;
    //         }

    //         // Perform check with visual error elements (passwords match)
    //         if(!checkPasswords()){
    //             if(signupConfirmPassword) signupConfirmPassword.focus();
    //             return;
    //         }

    //         alert('Account created successfully!');
    //         signupForm.reset();
    //         if (signupErrorMessage) signupErrorMessage.style.display = 'none';
    //         if (passwordRequirementsContainer) {
    //             passwordRequirementsContainer.querySelectorAll('.requirement').forEach(el => el.classList.remove('met'));
    //         }
    //         if (passwordCount) passwordCount.textContent = `(0/${passwordRequirements.length})`;
    //         if (signupSubmitButton) signupSubmitButton.disabled = true;
    //         closeSignupModal();
    //     });
    // }

    // Login modal functionality
    if(loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openLoginModal();
        });
    }

    if(applyLoginForm){
        applyLoginForm.addEventListener('submit', function(e){
            e.preventDefault();
            const email = document.getElementById('apply-email').value.trim();
            const password = document.getElementById('apply-password').value.trim();

            if(!email || !password){
                alert('Please enter your email and password.');
                return;
            }

            alert('Login successful!');
            closeApplyLoginModal();
        });
    }

    // Navigation menu toggle functionality
    if(menuToggle && topNav){
        menuToggle.addEventListener('click', function(){
            const isOpen = topNav.classList.toggle('open');
            menuToggle.classList.toggle('active', isOpen);
            menuToggle.setAttribute('aria-expanded', String(isOpen));
        });

    // Close menu when a link is clicked
        Array.from(topNav.querySelectorAll('a, button')).forEach(link => {
            link.addEventListener('click', function(){
                topNav.classList.remove('open');
                menuToggle.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            });
        });

    // Close menu on window resize if width exceeds 768px
        window.addEventListener('resize', function(){
            if(window.innerWidth > 768){
                topNav.classList.remove('open');
                menuToggle.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Slider functionality
    if(!slides.length) return;

    // Create navigation dots for each slide
    slides.forEach((s, i) => {
        const b = document.createElement('button');
        b.addEventListener('click', () => { show(i); restartTimer(); });
        if(i===0) b.classList.add('active');
        dotsContainer.appendChild(b);
    });

    // Show the slide at the given index and update dots
    function show(index){
        slides.forEach((s, i) => s.classList.toggle('active', i===index));
        const dots = Array.from(dotsContainer.children);
        dots.forEach((d,i)=>d.classList.toggle('active', i===index));
        current = index;
    }

    function next(){ show((current+1)%slides.length); }
    function restartTimer(){ clearInterval(timer); timer = setInterval(next, 4000); }

    const banner = document.querySelector('.banner');
    banner.addEventListener('mouseenter', ()=> clearInterval(timer));
    banner.addEventListener('mouseleave', ()=> restartTimer());

    timer = setInterval(next, 4000);
});

// Login Modal Functions
function openLoginModal() {
    const modal = document.getElementById('login-modal');
    if(modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// Close Login modal
function closeLoginModal() {
    const modal = document.getElementById('login-modal');
    if(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

function openApplyLoginModal() {
    const modal = document.getElementById('apply-login-modal');
    if(modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeApplyLoginModal() {
    const modal = document.getElementById('apply-login-modal');
    if(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// Demo credentials for each role
const demoCredentials = {
    admin: { email: 'admin@stmary.edu', password: 'admin123' },
    hr: { email: 'hr@stmary.edu', password: 'hr123' },
    accounting: { email: 'accounting@stmary.edu', password: 'acct123' },
    employee: { email: 'john.doe@stmary.edu', password: 'employee123' }
};

// Login tab switching
document.addEventListener('DOMContentLoaded', function() {
    const loginTabs = document.querySelectorAll('.login-tab');
    const credentialsText = document.getElementById('credentials-text');
    const loginForm = document.getElementById('login-form');
    let currentRole = 'admin'; // Track current selected role
    
    loginTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const role = this.dataset.role;
            currentRole = role; // Update current role
            
            // Update active tab
            loginTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update demo credentials
            if(demoCredentials[role]) {
                const creds = demoCredentials[role];
                const roleLabel = role.charAt(0).toUpperCase() + role.slice(1);
                credentialsText.innerHTML = `<strong>${roleLabel}:</strong> ${creds.email} / ${creds.password}`;
            }
        });
    });
    
    // Initialize with admin credentials
    if(loginTabs.length > 0) {
        const adminCreds = demoCredentials.admin;
        credentialsText.innerHTML = `<strong>Admin:</strong> ${adminCreds.email} / ${adminCreds.password}`;
    }

    // Handle login form submission
    if(loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            // Basic validation
            if(!email || !password) {
                alert('Please enter email and password');
                return;
            }

            const selectedCredentials = demoCredentials[currentRole];
            if(!selectedCredentials || email !== selectedCredentials.email || password !== selectedCredentials.password) {
                alert('Invalid email or password for the selected role');
                return;
            }
            
            // Route to dashboard based on selected role
            const dashboardRoutes = {
                admin: '../Admin_Dashboard/Admin_Dashboard.html',
                hr: '../HR_DASHBOARD/HR_DASHBOARD.html',
                accounting: '../ACCOUNTING_DASHBOARD/ACC_DASHBOARD.html',
                employee: '../EMPLOYEE_DASHBOARD/EMPLOYEE_DASHBOARD.html'
            };
            
            const dashboardURL = dashboardRoutes[currentRole];
            
            if(dashboardURL) {
                // Close modal and redirect
                closeLoginModal();
                // Store the logged-in role in sessionStorage for the dashboard
                sessionStorage.setItem('userRole', currentRole);
                sessionStorage.setItem('userEmail', email);
                // Redirect to dashboard
                window.location.href = dashboardURL;
            } else {
                alert('Invalid role selected');
            }
        });
    }

    // FIX: close the login / apply-login / facilities modals when the dark
    // area outside the box is clicked, and wire up their X buttons.
    // (Inline onclick on the same elements is harmless: closing twice is fine.)
    const modalClosers = {
        'login-modal': closeLoginModal,
        'apply-login-modal': closeApplyLoginModal,
        'FacilitiesModal': closeFacilitiesModal
    };

    Object.keys(modalClosers).forEach(function(id) {
        const overlay = document.getElementById(id);
        if(!overlay) return;

        // Click on the overlay itself (not on the content inside it)
        overlay.addEventListener('click', function(e) {
            if(e.target === overlay) modalClosers[id]();
        });

        // Any X / close button inside the modal
        overlay.querySelectorAll('[class*="close"]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                modalClosers[id]();
            });
        });
    });

    // Close modal on Escape key
    // FIX: every close call is now safe. Before, closeSignupModal and
    // closeApplyModal were not in scope here, and closeFacilitiesModal
    // crashed when the page has no facilities modal, so Escape stopped
    // working. closeApplyLoginModal was also missing.
    document.addEventListener('keydown', function(e) {
        if(e.key === 'Escape') {
            closeLoginModal();
            closeApplyLoginModal();
            closeFacilitiesModal();
            if(typeof window.closeSignupModal === 'function') window.closeSignupModal();
            if(typeof window.closeApplyModal === 'function') window.closeApplyModal();
        }
    });
});

// Facilities Modal Functions
function openFacilitiesModal(src, alt) {
    const FacilitiesModal = document.getElementById('FacilitiesModal');
    const img = document.getElementById('FacilitiesModal-img');
    if(!FacilitiesModal || !img) return; // FIX: null check
    img.src = src;
    img.alt = alt;
    FacilitiesModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close Facilities modal
function closeFacilitiesModal() {
    const FacilitiesModal = document.getElementById('FacilitiesModal');
    if(!FacilitiesModal) return; // FIX: null check (this was crashing Escape)
    FacilitiesModal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// FIX: when this file is loaded as a module (Laravel Vite), top-level
// functions are NOT global, so inline onclick="closeLoginModal()" does
// nothing. This makes the X buttons that use inline onclick work.
window.openLoginModal = openLoginModal;
window.closeLoginModal = closeLoginModal;
window.openApplyLoginModal = openApplyLoginModal;
window.closeApplyLoginModal = closeApplyLoginModal;
window.openFacilitiesModal = openFacilitiesModal;
window.closeFacilitiesModal = closeFacilitiesModal;
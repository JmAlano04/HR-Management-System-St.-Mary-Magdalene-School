<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/Home/HOMEPAGEStyle.css'])
    </head>
   <body>

    <header class="topbar">
        <a class="brand-link" href="HOMEPAGE.html" aria-label="Go to home page">
            <img class="brand-logo" src="{{ asset('Image/SMMS Logo.png') }}" alt="SMMS Logo">
            <span class="brand">St. Mary Magdalene School</span>
        </a>
        <button class="menu-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="top-nav">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="top-actions" id="top-nav">
            <button id="login-btn" class="top-btn primary" aria-label="Log in">Log In</button>
            <button id="apply-btn" class="top-btn primary" type="button" aria-haspopup="dialog" aria-controls="apply-modal">Apply Now</button>
        </nav>
    </header>

    <main>
        <section class="banner">
            <div class="container banner-container">
                <div class="slider" id="slider">
                    <div class="slide active slide--right">
                        <img src="{{ asset('Image/homepage1.jpg') }}" alt="banner 1">
                    </div>
                    <div class="slide slide--top">
                        <img src="{{ asset('Image/homepage2.png') }}" alt="banner 2">
                    </div>
                </div>

                <div class="banner-overlay">
                    <div class="banner-content"></div>
                </div>

                <div class="dots" id="dots"></div>
            </div>
        </section>

        <section class="history">
            <div class="container">
                <h1>History</h1>
                <div class="history-content">
                    <p>St. Mary Magdalene School (Parochial), Inc. was established in 1961 when Msgr. Eulogio San Juan, the Kawit Parish Priest, envisioned setting up a kindergarten class, which at that time was not being offered by any school in the town. With the help of Mr. Angel S. Colet, the convent became the first classroom, and Mrs. Angelita Quiamson Samaniego served as the pioneer teacher for fifteen enrolled pupils. In 1963, the Department of Education granted the Kindergarten class a permit to operate.</p>
                    <p>The name St. Mary Magdalene School was adopted in honor of the town’s patroness. March 11, 1966 was designated as the school’s Foundation Day, coinciding with the celebration of Msgr. Eulogio San Juan’s birthday. However, history goes beyond its chronological dates. To have a sense of history, it is very important to know where we have come from. Hence, on this day, we are reminded of how the school was formed 62 years ago. Today, we reflect on the school’s early days and how far we have come as a school community.</p>
                </div>
            </div>
        </section>

        <section class="facilities">
            <div class="container">
                <h1>School Facilities</h1>
                <div class="fac-grid">
                    <div class="fac-item" onclick="openFacilitiesModal('{{ asset("Image/facility1.jpg") }}', 'Facility 1')"><img src="{{ asset('Image/facility1.jpg') }}" alt="Facility 1"></div>
                    <div class="fac-item" onclick="openFacilitiesModal('{{ asset("Image/facility2.jpg") }}', 'Facility 2')"><img src="{{ asset('Image/facility2.jpg') }}" alt="Facility 2"></div>
                    <div class="fac-item" onclick="openFacilitiesModal('{{ asset("Image/facility3.jpg") }}', 'Facility 3')"><img src="{{ asset('Image/facility3.jpg') }}" alt="Facility 3"></div>
                </div>
            </div>
        </section>

        <!-- Apply Now Modal -->
        <div class="modal-overlay" id="apply-modal" hidden>
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="apply-modal-title">
             <button class="modal-close" type="button" aria-label="Close dialog">×</button>
                <h2 id="apply-modal-title">Continue your application</h2>
                <div class="modal-choice">
                 <div class="modal-choicetext">
                     <p>Already have an account?</p>
                     <span>Continue to login.</span>
                    </div>
                 <button class="modal-btn secondary" type="button" id="apply-login-btn">Login</button>
             </div>
                <div class="modal-choice">
                 <div class="modal-choicetext">
                     <p>Don't have an account?</p>
                     <span>Start your application.</span>
                    </div>
                     <button class="modal-btn primary" type="button" id="signup-open-btn">Sign up</button>
             </div>
            </div>
        </div>

        <!-- Sign Up Modal -->
        <div class="modal-overlay" id="signup-modal" hidden>
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="signup-modal-title">
                <button class="modal-close" type="button" aria-label="Close sign up dialog">×</button>
                <h2 id="signup-modal-title">Create your account</h2>
                <form id="signup-form" class="signup-form">
                    <div class="form-group">
                        <label for="signup-name">Full Name</label>
                        <input type="text" id="signup-name" placeholder="Enter your full name" required>
                    </div>
                    <div class="form-group">
                        <label for="signup-email">Email Address</label>
                        <input type="email" id="signup-email" placeholder="Enter your email address" required>
                    </div>
                    <div class="form-group">
                        <label for="signup-password">Password</label>
                        <div class="password-input-wrap">
                            <input type="password" id="signup-password" placeholder="Create a password" required>
                            <button class="password-toggle" type="button" data-target="signup-password" aria-label="Show password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </button>
                        </div>
                        <div id="password-requirements" class="password-requirements" aria-live="polite">
                            <div class="pr-header"><strong>Password requirements</strong> <span id="password-count">(0/4)</span></div>
                            <div class="requirement" data-requirement="length"><span class="dot"></span><span class="text">Minimum 12 characters</span></div>
                            <div class="requirement" data-requirement="capital"><span class="dot"></span><span class="text">One capitalized letter</span></div>
                            <div class="requirement" data-requirement="number"><span class="dot"></span><span class="text">One number</span></div>
                            <div class="requirement" data-requirement="symbol"><span class="dot"></span><span class="text">At least one non letter / number (i.e. !@#$%^&*)</span></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="signup-confirm-password">Confirm Password</label>
                        <div class="password-input-wrap">
                            <input type="password" id="signup-confirm-password" placeholder="Confirm your password" required>
                            <button class="password-toggle" type="button" data-target="signup-confirm-password" aria-label="Show password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Password Mismatch Error Element -->
                        <span id="signup-error-message" class="error-text">Passwords do not match</span>
                    </div>
                    <button type="submit" class="login-submit">Submit</button>
                </form>
            </div>
        </div>
        
        <!-- Apply Login Modal -->
        <div id="apply-login-modal" class="login-modal" onclick="closeApplyLoginModal()">
            <div class="login-modal-content" onclick="event.stopPropagation();">
                <span class="login-modal-close" onclick="closeApplyLoginModal();">&times;</span>
                <div class="login-modal-inner">
                    <div class="login-header">
                        <h2>Login to Apply</h2>
                    </div>
                    <form id="apply-login-form" class="login-form">
                        <div class="form-group">
                            <label for="apply-email">Email Address</label>
                            <input type="email" id="apply-email" placeholder="Email address" required>
                        </div>
                        <div class="form-group">
                            <label for="apply-password">Password</label>
                            <div class="password-input-wrap">
                                <input type="password" id="apply-password" placeholder="Password" required>
                                <button class="password-toggle" type="button" data-target="apply-password" aria-label="Show password" aria-pressed="false">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="form-row">
                            <label class="remember-me"><input type="checkbox" id="apply-remember"> Remember me</label>
                            <a href="#" class="forgot-link">Forgot password?</a>
                        </div>
                        <button type="submit" class="login-submit">Login</button>
                    </form>
                    <div class="login-note">Don’t have an account? <a href="#" id="apply-login-signup-link">Sign up</a></div>
                </div>
            </div>
        </div>

        <!-- Facilities Modal -->
        <div id="FacilitiesModal" class="FacilitiesModal" onclick="closeFacilitiesModal()">
            <span class="FacilitiesModal-close" onclick="closeFacilitiesModal(); event.stopPropagation();">&times;</span>
            <img class="FacilitiesModal-img" id="FacilitiesModal-img" src="" alt="">
        </div>

        <!-- Login Modal -->
        <div id="login-modal" class="login-modal" onclick="closeLoginModal()">
            <div class="login-modal-content" onclick="event.stopPropagation();">
                <span class="login-modal-close" onclick="closeLoginModal();">&times;</span>
                <div class="login-modal-inner">
                    <div class="login-header">
                        <img src="image/SMMS Logo.png" alt="School Logo" class="login-logo">
                        <h2>St. Mary Magdalene School</h2>
                    </div>
                    <div class="login-tabs">
                        <button class="login-tab active" data-role="admin">Admin</button>
                        <button class="login-tab" data-role="hr">HR</button>
                        <button class="login-tab" data-role="accounting">Accounting</button>
                        <button class="login-tab" data-role="employee">Employee</button>
                    </div>
                    <form id="login-form" class="login-form">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-input-wrap">
                                <input type="password" id="password" placeholder="Enter your password" required>
                                <button class="password-toggle" type="button" data-target="password" aria-label="Show password" aria-pressed="false">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="login-submit">Log In</button>
                    </form>
                <div class="demo-credentials" id="demo-credentials">
                    <h3>Demo Credentials</h3>
                    <p id="credentials-text">Choose a role button to view the matching demo email and password.</p>
                    </div>
                </div>
            </div>
        </div>
    </main> 

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-copyright">
                    <p>Diocese of Imus Catholic Educational System, Inc. © Copyright 2026</p>
                </div>
                <div class="contact-info">
                    <a href="https://mail.google.com/mail/?view=cm&fs=1&to=communications@smms.ph.education" class="contact-link" target="_blank" rel="noopener noreferrer" aria-label="Gmail">
                        <svg class="contact-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                            <rect x="8" y="12" width="48" height="36" rx="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 12L32 28L56 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <a href="https://www.google.com/maps/place/Diocesan+Shrine+and+Parish+of+St.+Mary+Magdalene+-+Poblacion,+Kawit,+Cavite/@14.4448796,120.9043053,3a,75y,261.45h,86.37t/data=!3m7!1e1!3m5!1sDOP874WxLwA2pwV_zHwB_w!2e0!6shttps:%2F%2Fstreetviewpixels-pa.googleapis.com%2Fv1%2Fthumbnail%3Fcb_client%3Dmaps_sv.tactile%26w%3D900%26h%3D600%26pitch%3D3.6339895959135333%26panoid%3DDOP874WxLwA2pwV_zHwB_w%26yaw%3D261.4517119875302!7i16384!8i8192!4m14!1m7!3m6!1s0x3397d2b1b864c587:0x6480789328248ca2!2sDiocesan+Shrine+and+Parish+of+St.+Mary+Magdalene+-+Poblacion,+Kawit,+Cavite!8m2!3d14.4450062!4d120.9034507!16s%2Fm%2F0j63dhg!3m5!1s0x3397d2b1b864c587:0x6480789328248ca2!8m2!3d14.4450062!4d120.9034507!16s%2Fm%2F0j63dhg?entry=ttu&g_ep=EgoyMDI2MDYyOS4wIKXMDSoASAFQAw%3D%3D" class="contact-link" target="_blank" rel="noopener noreferrer" aria-label="Google Maps">
                        <svg class="contact-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                            <path d="M32 8C22.06 8 14 16.06 14 26c0 12 18 32 18 32s18-20 18-32c0-9.94-8.06-18-18-18z" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="32" cy="26" r="6" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </a>
                    <a href="https://www.facebook.com/SMMSchool1963" class="contact-link" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <svg class="contact-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                            <path d="M32 8C19.3 8 9 18.3 9 31c0 12.7 10.3 23 23 23s23-10.3 23-23-10.3-23-23-23zm10 24h-6v18h-7V32h-4v-7h4v-5c0-6 2-8 7-8h6v6h-4c-2 0-2.5 1-2.5 3v4h6.5l-2 7z" fill="currentColor"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="../shared-sync.js"></script>
     @vite(['resources/js/Home/HOMEPAGEScript.js'])
</body>
</html>

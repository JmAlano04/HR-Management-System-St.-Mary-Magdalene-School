@extends('layouts.home')

@section('title', 'Home | St. Mary Magdalene School')

{{-- STYLES: everything from HOMEPAGEStyle.css, inlined --}}
@section('styles')
@verbatim
<style>
:root {
    --primary: #b22222;
    --primary-contrast: #f7f7f7;
    --muted: #f7f7f7;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    height: 100%;
    margin: 0;
    font-family: Inter, system-ui, Segoe UI, Roboto, 'Helvetica Neue', Arial;
    color: #222;
    background: var(--muted);
}

body {
    max-width: 1920px;
    height: 1080px;
    margin: 0 auto;
    padding-top: 90px;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 48px 20px 20px;
    background: transparent linear-gradient(to bottom, rgba(229, 228, 228, 0.977), rgba(255, 255, 255, 0.977));
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 30;
    box-shadow: 0 6px 10px rgba(0, 0, 0, 0.08);
    max-width: 1920px;
    margin: 0 auto;
}

.brand-link {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.brand-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
    flex-shrink: 0;
}

.brand {
    font-weight: 800;
    color: var(--primary);
    font-size: clamp(18px, 2.2vw, 30px);
    line-height: 1.2;
}

.brand-link:hover .brand {
    color: #8b1a1a;
}

.top-actions {
    display: flex;
    gap: 14px;
    align-items: center;
}

.menu-toggle {
    display: none;
    flex-direction: column;
    justify-content: center;
    gap: 4px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
}

.menu-toggle span {
    display: block;
    width: 24px;
    height: 2px;
    background: var(--primary);
    border-radius: 999px;
    transition: transform 0.25s ease, opacity 0.25s ease;
}

.menu-toggle.active span:nth-child(1) { transform: translateY(6px) rotate(45deg); }
.menu-toggle.active span:nth-child(2) { opacity: 0; }
.menu-toggle.active span:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

.top-btn {
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    color: var(--primary);
    border: 1px solid var(--primary);
    font-weight: 700;
    font-size: 16px;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.top-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transition: left 0.3s ease;
    z-index: -1;
}

.top-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(178, 34, 34, 0.25);
}

.top-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 8px rgba(178, 34, 34, 0.15);
}

.top-btn.primary {
    background: var(--primary);
    color: var(--primary-contrast);
    border: 1px solid var(--primary);
}

.top-btn.primary:hover {
    background: #ffffff;
    color: var(--primary);
    border-color: #ffffff;
    box-shadow: 0 8px 20px rgba(178, 34, 34, 0.35);
}

.top-btn.primary:active {
    background: #f7f7f7;
    color: var(--primary);
    border-color: #f7f7f7;
}

/* ---------- Banner ---------- */
.banner {
    position: relative;
    overflow: hidden;
    height: 460px;
    background: #ffffff;
}

.banner .container {
    width: 100%;
    margin: 0 auto;
    position: relative;
    height: 100%;
    padding: 0 24px;
}

.slider {
    position: absolute;
    inset: 0;
}

.slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 1s ease;
    display: flex;
}

.slide img {
    display: flex;
    height: 100%;
    width: 100%;
    object-fit: cover;
    object-position: center center;
    background: var(--muted);
    filter: brightness(0.82) contrast(0.96);
    transform-origin: center center;
    transition: transform 0.9s ease;
    will-change: transform;
}

.slide.active { opacity: 1; }

.slide--center img { object-position: center center; }
.slide--top img { object-position: center top; }
.slide--left img { object-position: left center; }
.slide--right img {
    object-position: 70% center;
    transform-origin: 70% center;
}

.banner-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
}

.banner-content {
    width: calc(100% - 64px);
    margin-left: 64px;
    color: var(--primary-contrast);
    z-index: 10;
    padding-top: 80px;
}

.banner-content h1 {
    font-size: clamp(28px, 5vw, 48px);
    margin: 0 0 12px 0;
    letter-spacing: 0.2px;
}

.banner-content p {
    margin: 0 0 18px 0;
    font-size: clamp(14px, 2vw, 18px);
    opacity: 0.95;
}

.banner-content h1, .banner-content p {
    text-shadow: 0 6px 22px rgba(0,0,0,0.6);
}

.cta {
    display: flex;
    gap: 14px;
}

.btn {
    display: inline-block;
    padding: 12px 22px;
    border-radius: 8px;
    text-decoration: none;
    background: transparent;
    color: var(--primary-contrast);
    border: 2px solid rgba(255,255,255,0.9);
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(4px);
}

.btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
    z-index: -1;
}

.btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn:hover {
    transform: scale(1.05);
    border-color: rgba(255,255,255,1);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.btn:active { transform: scale(0.98); }

.btn.primary {
    background: var(--primary);
    border-color: var(--primary);
}

.btn.primary:hover {
    background: #8b1a1a;
    border-color: #8b1a1a;
    box-shadow: 0 10px 25px rgba(139, 26, 26, 0.4);
    transform: scale(1.08);
}

.btn.primary:active {
    transform: scale(0.95);
    background: #6b1515;
}

.dots {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    bottom: 22px;
    display: flex;
    gap: 8px;
    z-index: 20;
}

.dots button {
    width: 11px;
    height: 11px;
    border: 0;
    border-radius: 999px;
    background: var(--primary-contrast);
    cursor: pointer;
    transition: transform 0.18s ease, background-color 0.18s ease, width 0.18s ease;
}

.dots button.active {
    width: 15px;
    background: var(--primary);
}

.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
    background: transparent;
    color: #1a1a1a;
    padding: 20px;
}

/* ---------- History ---------- */
.history {
    margin-top: 20px;
    padding: 60px 20px;
    background: var(--primary-contrast);
}

.history .container {
    width: 100%;
    margin: 0 auto;
}

.history h1, .facilities h1 {
    color: #b22222;
    text-align: center;
    font-size: clamp(32px, 5vw, 50px);
    margin-bottom: 30px;
    font-weight: 700;
}

.history .history-content {
    width: 100%;
    padding: 40px 20px;
    border: 1px solid rgba(178, 34, 34, 0.2);
    border-radius: 20px;
    background: #ffffff;
    color: #3b3b3b;
    line-height: 1.8;
    text-align: justify;
    box-shadow: 0px 20px 20px rgba(0,0,0,0.08);
}

.history .history-content p {
    margin: 0 0 18px 0;
    font-size: 16px;
}

/* ---------- Facilities ---------- */
.facilities {
    margin-top: 20px;
    padding: 60px 20px;
    background: #f7f7f7;
}

.facilities .container {
    width: 100%;
    margin: 0 auto;
}

.facilities h2 {
    color: #3f3fdc;
    text-align: center;
    font-size: clamp(24px, 4vw, 34px);
    margin-bottom: 28px;
    font-weight: 700;
}

.fac-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    justify-items: center;
    align-items: stretch;
    padding: 0 10px;
}

.fac-item {
    width: 100%;
    max-width: 420px;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 50px 50px rgba(0,0,0,0.08);
    cursor: pointer;
}

.fac-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.fac-item:hover img { transform: scale(1.50); }

.FacilitiesModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.85);
    z-index: 100;
    justify-content: center;
    align-items: center;
    cursor: pointer;
}

.FacilitiesModal.active { display: flex; }

.FacilitiesModal-img {
    max-width: 90%;
    max-height: 90%;
    border-radius: 6px;
    box-shadow: 0 0 30px rgba(0,0,0,0.8);
}

.FacilitiesModal-close {
    position: absolute;
    top: 20px;
    right: 30px;
    font-size: 40px;
    font-weight: bold;
    color: white;
    cursor: pointer;
    z-index: 101;
    transition: color 0.3s ease;
}

.FacilitiesModal-close:hover { color: #b22222; }

/* ---------- Password requirements ---------- */
.password-requirements {
    margin-top: 10px;
    padding: 10px 12px;
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 10px;
    font-size: 13px;
    color: #222;
}

.password-requirements .requirement {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 6px 0;
}

.password-requirements .dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: transparent;
    border: 2px solid #111;
    display: inline-block;
    flex-shrink: 0;
    position: relative;
}

.password-requirements .requirement.met .dot {
    background: #2ecc71;
    border-color: #2ecc71;
    box-shadow: 0 0 0 4px rgba(46,204,113,0.08);
}

.password-requirements .dot::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -52%);
    font-size: 11px;
    color: white;
}

.password-requirements .requirement.met .dot::after { content: '\2713'; }

.password-requirements .text { line-height: 1.1; }

.password-requirements .pr-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 13px;
}

.password-requirements .requirement.met .text {
    color: #2ecc71;
    font-weight: 600;
}

.signup-form button[type="submit"]:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.error-text {
    color: #b30000;
    display: none;
    margin-top: 8px;
    font-size: 0.85rem;
    font-family: sans-serif;
}

/* ---------- Footer ---------- */
.footer {
    background: var(--primary);
    padding: 40px 20px 30px;
    text-align: center;
}

.footer-container {
    width: 100%;
    margin: 0 auto;
}

.footer-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.contact-info {
    display: flex;
    gap: 20px;
    align-items: center;
    justify-content: flex-end;
}

.contact-link {
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.4);
}

.contact-link:hover {
    transform: scale(1.15);
    background: rgba(255, 255, 255, 0.35);
    border-color: rgba(255, 255, 255, 0.7);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.contact-link:active { transform: scale(0.95); }

.contact-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-contrast);
    font-weight: 700;
    stroke: var(--primary-contrast);
}

.footer-copyright {
    flex: 1;
    min-width: 200px;
}

.footer-copyright p {
    color: var(--primary-contrast);
    font-size: 13px;
    font-weight: 600;
}

/* ---------- Apply / Sign-up modal ---------- */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(17, 17, 17, 0.58);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 100;
}

.modal-overlay[hidden] { display: none; }

.modal-card {
    position: relative;
    width: min(100%, 460px);
    background: linear-gradient(135deg, #ffffff 0%, #fff8f8 100%);
    border: 1px solid rgba(178, 34, 34, 0.2);
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    padding: 32px 24px 24px;
    text-align: center;
}

.modal-close {
    position: absolute;
    top: 12px;
    right: 14px;
    border: none;
    background: transparent;
    color: var(--primary);
    font-size: 26px;
    cursor: pointer;
}

.modal-card h2 {
    color: var(--primary);
    margin-bottom: 22px;
    font-size: 24px;
}

.signup-form { text-align: left; }

.password-input-wrap { position: relative; }
.password-input-wrap input { padding-right: 46px; }

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    color: #777;
    cursor: pointer;
    padding: 0;
}

.password-toggle:hover { color: var(--primary); }
.password-toggle svg { width: 20px; height: 20px; }

.modal-choice {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 16px;
    margin-bottom: 12px;
    border: 1px solid rgba(178, 34, 34, 0.14);
    border-radius: 14px;
    background: #f7f7f7;
}

.modal-choicetext {
    text-align: left;
    flex: 1;
}

.modal-choicetext p {
    color: #222;
    margin: 0 0 4px;
    font-weight: 700;
    line-height: 1.3;
}

.modal-choice__text span {
    display: block;
    color: #777;
    font-size: 13px;
    line-height: 1.4;
}

.modal-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 16px;
    min-width: 92px;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 700;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.modal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(178, 34, 34, 0.25);
}

.modal-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 8px rgba(178, 34, 34, 0.15);
}

.modal-btn.primary,
.modal-btn.secondary {
    background: var(--primary);
    color: var(--primary-contrast);
    border: 1px solid var(--primary);
}

.modal-btn.primary:hover,
.modal-btn.secondary:hover {
    background: var(--primary-contrast);
    color: var(--primary);
    border-color: var(--primary-contrast);
    box-shadow: 0 8px 20px rgba(178, 34, 34, 0.35);
}

.modal-btn.primary:active,
.modal-btn.secondary:active {
    background: #f7f7f7;
    color: var(--primary);
    border-color: #f7f7f7;
}

.form-row {
    display: flex;
    align-items: center;
    justify-content: end;
    gap: 12px;
    margin-bottom: 18px;
    flex-wrap: wrap;
}

.remember-me {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    background-color: red;
    font-size: 13px;
    color: #444;
}

.remember-me input { accent-color: var(--primary); }

.forgot-link {
    color: var(--primary);
    text-decoration: none;
    font-size: 13px;
}

.forgot-link:hover { text-decoration: underline; }

.login-note {
    margin-top: 14px;
    text-align: center;
    font-size: 13px;
    color: #444;
}

.login-note a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 700;
}

.login-note a:hover { text-decoration: underline; }

/* ---------- Login modal ---------- */
.login-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: flex-start;
    padding-top: 60px;
    cursor: pointer;
    overflow-y: auto;
}

.login-modal.active { display: flex; }

.login-modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 420px;
    position: relative;
    cursor: auto;
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from { opacity: 0; transform: translateY(-30px); }
    to   { opacity: 1; transform: translateY(0); }
}

.login-modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 32px;
    font-weight: bold;
    color: #999;
    cursor: pointer;
    z-index: 1001;
    transition: color 0.3s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-modal-close:hover { color: #b22222; }

.login-modal-inner { padding: 40px 24px 24px; }

.login-header {
    text-align: center;
    margin-bottom: 24px;
}

.login-logo {
    width: 65px;
    height: 65px;
    object-fit: contain;
    margin-bottom: 12px;
}

.login-header h2 {
    color: #b22222;
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.login-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    justify-content: center;
    flex-wrap: wrap;
}

.login-tab {
    padding: 8px 16px;
    border: 2px solid #ddd;
    background: white;
    border-radius: 8px;
    color: #b22222;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 13px;
}

.login-tab:hover {
    border-color: #b22222;
    background: rgba(178, 34, 34, 0.05);
}

.login-tab.active {
    background: white;
    border-color: #b22222;
    color: #b22222;
}

.login-form { margin-bottom: 18px; }
.form-group { margin-bottom: 16px; }

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
    font-size: 13px;
}

.form-group input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 13px;
    font-family: Inter, system-ui, Segoe UI, Roboto, 'Helvetica Neue', Arial;
    transition: border-color 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: #b22222;
    box-shadow: 0 0 0 3px rgba(178, 34, 34, 0.1);
}

.form-group input::placeholder { color: #999; }

.login-submit {
    width: 100%;
    padding: 12px;
    background: #b22222;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
}

.login-submit:hover {
    background: #8b1a1a;
    box-shadow: 0 4px 12px rgba(178, 34, 34, 0.3);
}

.login-submit:active { transform: scale(0.98); }

.demo-credentials {
    background: rgba(178, 34, 34, 0.08);
    border: 1px solid rgba(178, 34, 34, 0.2);
    border-radius: 8px;
    padding: 12px;
}

.demo-credentials h3 {
    color: #b22222;
    font-size: 13px;
    font-weight: 700;
    margin: 0 0 6px 0;
}

.demo-credentials p {
    color: #555;
    font-size: 12px;
    line-height: 1.4;
    margin: 0;
}

/* ---------- Media queries ---------- */
@media (max-width: 1024px) {
    .topbar { padding: 20px 24px; }

    .banner { height: 550px; }

    .banner-content {
        width: calc(100% - 32px);
        margin-left: 16px;
        padding-top: 60px;
    }

    .footer-content {
        flex-direction: column;
        gap: 15px;
    }

    .contact-info {
        justify-content: center;
        width: 100%;
    }

    .footer-copyright {
        width: 100%;
        flex: none;
    }
}

@media (max-width: 768px) {
    .menu-toggle { display: flex; }

    .top-actions {
        display: none;
        position: absolute;
        top: calc(100% + 12px);
        right: 24px;
        flex-direction: column;
        align-items: stretch;
        background: var(--primary-contrast);
        padding: 12px;
        border-radius: 10px;
        box-shadow: 0 10px 24px rgba(0,0,0,0.14);
        min-width: 180px;
        z-index: 40;
    }

    .top-actions.open { display: flex; }

    .top-actions .top-btn {
        width: 100%;
        text-align: center;
    }

    .banner { height: 450px; }

    .footer-content {
        flex-direction: column;
        justify-content: center;
    }

    .contact-info {
        justify-content: center;
        width: 100%;
        gap: 15px;
    }

    .contact-link {
        width: 45px;
        height: 45px;
    }

    .contact-icon {
        width: 24px;
        height: 24px;
    }

    .footer-copyright {
        width: 100%;
        text-align: center;
    }

    .footer-copyright p { font-size: 12px; }

    .login-modal-content {
        width: 90%;
        margin: 0 auto;
    }

    .login-modal-inner { padding: 32px 20px 20px; }
    .login-header h2 { font-size: 24px; }

    .login-logo {
        width: 60px;
        height: 60px;
    }

    .login-tabs { gap: 8px; }

    .login-tab {
        padding: 8px 14px;
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .topbar { padding: 15px 12px; }

    .banner { height: 300px; }

    .brand { font-size: 16px; }

    .brand-logo {
        width: 45px;
        height: 45px;
    }

    .footer { padding: 25px 15px 20px; }

    .contact-info { gap: 12px; }

    .contact-link {
        width: 40px;
        height: 40px;
    }

    .contact-icon {
        width: 20px;
        height: 20px;
    }

    .footer-copyright p {
        font-size: 11px;
        line-height: 1.4;
    }

    .modal-card { padding: 28px 18px 20px; }

    .modal-choice {
        flex-direction: column;
        align-items: flex-start;
    }

    .modal-btn { width: 100%; }
}

/* ---------- Extra responsive rules ---------- */
@media (max-width: 1024px) {
    body { height: auto; min-height: 100%; padding-top: 100px; overflow-x: hidden; }
    img, svg, video { max-width: 100%; }
    .history, .facilities { padding: 48px 20px; }
    .history .history-content { padding: 32px 24px; }
    .banner-content h1 { font-size: clamp(24px, 4.5vw, 40px); }
}

@media (max-width: 768px) {
    .topbar { gap: 12px; }
    .brand-link { min-width: 0; flex: 1; }
    .brand {
        font-size: clamp(15px, 4.2vw, 20px);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .top-actions { right: 16px; }
    .top-btn { min-height: 44px; }
    .menu-toggle {
        min-width: 44px;
        min-height: 44px;
        align-items: center;
        flex-shrink: 0;
    }
    .menu-toggle span { margin: 0; }
    .banner .container { padding: 0; }
    .banner-content { margin-left: 16px; padding-top: 32px; }
    .history, .facilities { padding: 40px 16px; margin-top: 0; }
    .history .history-content {
        padding: 24px 18px;
        text-align: left;
        border-radius: 16px;
    }
    .history .history-content p { font-size: 15px; line-height: 1.7; }
    .history h1, .facilities h1 { margin-bottom: 22px; }
    .fac-grid { gap: 16px; padding: 0; }
    .fac-item { max-width: 100%; box-shadow: 0 20px 30px rgba(0,0,0,0.08); }
    .footer { padding: 28px 16px 22px; }
    .login-modal { padding: 24px 12px; align-items: flex-start; }
    .login-modal-content { width: 100%; max-width: 420px; margin: 0 auto; }
    .modal-overlay { padding: 12px; overflow-y: auto; align-items: flex-start; }
    .modal-card { max-height: calc(100vh - 24px); overflow-y: auto; margin: auto; }
    .FacilitiesModal-close { top: 12px; right: 18px; }
}

@media (max-width: 480px) {
    body { padding-top: 76px; }
    .brand { font-size: clamp(13px, 4.4vw, 16px); }
    .top-actions { right: 12px; min-width: 160px; }
    .banner-content { width: calc(100% - 32px); }
    .history h1, .facilities h1 { font-size: 28px; }
    .login-modal-inner { padding: 28px 16px 18px; }
    .login-tabs { gap: 6px; }
    .login-tab { flex: 1 1 calc(50% - 6px); text-align: center; }
    .form-row { justify-content: space-between; }
    .FacilitiesModal-img { max-width: 96%; }
}

@media (hover: none) {
    .fac-item:hover img { transform: none; }
}

@media (max-width: 360px) {
    .fac-grid { grid-template-columns: 1fr; }
    .brand-logo { width: 38px; height: 38px; }
    .brand-link { gap: 8px; }
    .modal-card { padding: 24px 14px 18px; }
}

@media (max-height: 520px) and (orientation: landscape) {
    .login-modal { padding-top: 12px; }
    .banner { height: 70vh; }
}

@media (prefers-reduced-motion: reduce) {
    * { animation: none !important; transition: none !important; }
}
</style>
@endverbatim
@endsection



@section('content')

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
            @auth
            <a href="{{ route('dashboard') }}">
                <button class="top-btn primary" aria-label="Dashboard">Dashboard</button>
            </a>
            @else
            <button id="login-btn" class="top-btn primary" aria-label="Log in">Log In</button>
            @endauth
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

        {{-- Apply Now Modal --}}
        <x-apply-now-modal />

        {{-- registration --}}
        <x-registration-modal/>

        <!-- Facilities Modal -->
        <div id="FacilitiesModal" class="FacilitiesModal" onclick="closeFacilitiesModal()">
            <span class="FacilitiesModal-close" onclick="closeFacilitiesModal(); event.stopPropagation();">&times;</span>
            <img class="FacilitiesModal-img" id="FacilitiesModal-img" src="" alt="">
        </div>

        {{-- Apply Login Modal --}}
        <x-apply-login-modal />

        {{-- Login Modal --}}
        <x-login-modal />

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
@endsection


{{-- =====================================================================
     SCRIPTS (everything from HOMEPAGEScript.js, inlined)
     ===================================================================== --}}
@section('scripts')

    {{-- Kept as-is: not part of the design/interactivity files you sent --}}
    @vite(['resources/js/shared-sync.js'])

@verbatim
<script>
/* ------------------------------------------------------------------
   Modal helpers (defined at top level so inline onclick="..." works)
   ------------------------------------------------------------------ */

// Login modal
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
        document.body.style.overflow = 'auto';
    }
}

// Apply-login modal
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
        document.body.style.overflow = 'auto';
    }
}

// Facilities modal
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
    document.body.style.overflow = 'auto';
}

window.openLoginModal = openLoginModal;
window.closeLoginModal = closeLoginModal;
window.openApplyLoginModal = openApplyLoginModal;
window.closeApplyLoginModal = closeApplyLoginModal;
window.openFacilitiesModal = openFacilitiesModal;
window.closeFacilitiesModal = closeFacilitiesModal;


/* ------------------------------------------------------------------
   Main page behaviour: slider, nav menu, apply + signup modals
   ------------------------------------------------------------------ */
document.addEventListener('DOMContentLoaded', function () {
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

    // ----- Apply modal -----
    function openApplyModal() {
        if (!applyModal) return;
        applyModal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeApplyModal() {
        if (!applyModal) return;
        applyModal.hidden = true;
        document.body.style.overflow = '';
    }

    // ----- Sign-up modal -----
    function openSignupModal() {
        if (!signupModal) return;
        signupModal.hidden = false;
        document.body.style.overflow = 'hidden';
        const sp = document.getElementById('signup-password');
        const scp = document.getElementById('signup-confirm-password');
        const pr = document.getElementById('password-requirements');
        if (pr) pr.style.display = 'block';

        if (!signupHandlersAttached) {
            if (sp) {
                sp.addEventListener('input', function () {
                    updatePasswordRequirements(sp.value || '');
                    checkPasswords();
                });
                updatePasswordRequirements(sp.value || '');
            }
            if (scp) {
                scp.addEventListener('input', checkPasswords);
            }
            signupHandlersAttached = true;
        }
    }

    function closeSignupModal() {
        if (!signupModal) return;
        signupModal.hidden = true;
        document.body.style.overflow = '';
    }

    // Make these reachable from the Escape handler and inline onclick
    window.openApplyModal = openApplyModal;
    window.closeApplyModal = closeApplyModal;
    window.openSignupModal = openSignupModal;
    window.closeSignupModal = closeSignupModal;

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

    const applyLoginBtn = document.getElementById('apply-login-btn');
    const applyLoginModal = document.getElementById('apply-login-modal');
    const applyLoginForm = document.getElementById('apply-login-form');
    const applyLoginSignupLink = document.getElementById('apply-login-signup-link');

    if (signupOpenBtn && signupModal) {
        signupOpenBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeApplyModal();
            openSignupModal();
        });
    }

    if (applyLoginBtn && applyLoginModal) {
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

    if (signupCloseBtn) {
        signupCloseBtn.addEventListener('click', closeSignupModal);
    }

    if (signupModal) {
        signupModal.addEventListener('click', function (e) {
            if (e.target === signupModal) closeSignupModal();
        });
    }

    // ----- Password visibility toggles -----
    function updatePasswordIcon(toggle, isVisible) {
        if (isVisible) {
            toggle.innerHTML = `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>`;
        } else {
            toggle.innerHTML = `<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 17l-10-10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
        }
    }

    Array.from(document.querySelectorAll('.password-toggle')).forEach(toggle => {
        toggle.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            this.setAttribute('aria-pressed', String(shouldShow));
            this.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            updatePasswordIcon(this, shouldShow);
        });
    });

    // ----- Sign-up password validation -----
    const signupPassword = document.getElementById('signup-password');
    const signupConfirmPassword = document.getElementById('signup-confirm-password');
    const signupErrorMessage = document.getElementById('signup-error-message');
    const passwordRequirementsContainer = document.getElementById('password-requirements');
    const passwordCount = document.getElementById('password-count');
    const signupSubmitButton = signupForm ? signupForm.querySelector('button[type="submit"]') : null;
    if (signupSubmitButton) signupSubmitButton.disabled = true;

    const passwordRequirements = [
        { key: 'length',  test: p => p.length >= 12 },
        { key: 'capital', test: p => /[A-Z]/.test(p) },
        { key: 'number',  test: p => /\d/.test(p) },
        { key: 'symbol',  test: p => /[^A-Za-z0-9]/.test(p) }
    ];

    function updatePasswordRequirements(pass) {
        if (!passwordRequirementsContainer) return;
        let metCount = 0;
        passwordRequirements.forEach(r => {
            const el = passwordRequirementsContainer.querySelector(`.requirement[data-requirement="${r.key}"]`);
            if (!el) return;
            const met = r.test(pass);
            if (met) { el.classList.add('met'); metCount++; } else { el.classList.remove('met'); }
        });
        if (passwordCount) passwordCount.textContent = `(${metCount}/${passwordRequirements.length})`;
        if (signupSubmitButton) signupSubmitButton.disabled = metCount !== passwordRequirements.length;
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

    // ----- Login button -----
    if (loginBtn) {
        loginBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openLoginModal();
        });
    }

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

    // ----- Mobile navigation menu -----
    if (menuToggle && topNav) {
        menuToggle.addEventListener('click', function () {
            const isOpen = topNav.classList.toggle('open');
            menuToggle.classList.toggle('active', isOpen);
            menuToggle.setAttribute('aria-expanded', String(isOpen));
        });

        Array.from(topNav.querySelectorAll('a, button')).forEach(link => {
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

    // ----- Banner slider -----
    if (!slides.length) return;

    slides.forEach((s, i) => {
        const b = document.createElement('button');
        b.addEventListener('click', () => { show(i); restartTimer(); });
        if (i === 0) b.classList.add('active');
        dotsContainer.appendChild(b);
    });

    function show(index) {
        slides.forEach((s, i) => s.classList.toggle('active', i === index));
        Array.from(dotsContainer.children).forEach((d, i) => d.classList.toggle('active', i === index));
        current = index;
    }

    function next() { show((current + 1) % slides.length); }
    function restartTimer() { clearInterval(timer); timer = setInterval(next, 4000); }

    const banner = document.querySelector('.banner');
    banner.addEventListener('mouseenter', () => clearInterval(timer));
    banner.addEventListener('mouseleave', () => restartTimer());

    timer = setInterval(next, 4000);
});


/* ------------------------------------------------------------------
   Login tabs, demo login, overlay-click closing, Escape key
   ------------------------------------------------------------------ */
const demoCredentials = {
    admin:      { email: 'admin@stmary.edu',      password: 'password123' },
    hr:         { email: 'hr@stmary.edu',         password: 'password123' },
    accounting: { email: 'accounting@stmary.edu', password: 'password123' },
    employee:   { email: 'john.doe@stmary.edu',   password: 'password123' }
};

document.addEventListener('DOMContentLoaded', function () {
    const loginTabs = document.querySelectorAll('.login-tab');
    const credentialsText = document.getElementById('credentials-text');
    const loginForm = document.getElementById('login-form');
    let currentRole = 'admin';

    loginTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const role = this.dataset.role;
            currentRole = role;

            loginTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            if (demoCredentials[role] && credentialsText) {
                const creds = demoCredentials[role];
                const roleLabel = role.charAt(0).toUpperCase() + role.slice(1);
                credentialsText.innerHTML = `<strong>${roleLabel}:</strong> ${creds.email} / ${creds.password}`;
            }
        });
    });

    if (loginTabs.length > 0 && credentialsText) {
        const adminCreds = demoCredentials.admin;
        credentialsText.innerHTML = `<strong>Admin:</strong> ${adminCreds.email} / ${adminCreds.password}`;
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!email || !password) {
                alert('Please enter email and password');
                return;
            }

            const selectedCredentials = demoCredentials[currentRole];
            if (!selectedCredentials || email !== selectedCredentials.email || password !== selectedCredentials.password) {
                alert('Invalid email or password for the selected role');
                return;
            }

            const dashboardRoutes = {
                admin:      '../Admin_Dashboard/Admin_Dashboard.html',
                hr:         '../HR_DASHBOARD/HR_DASHBOARD.html',
                accounting: '../ACCOUNTING_DASHBOARD/ACC_DASHBOARD.html',
                employee:   '../EMPLOYEE_DASHBOARD/EMPLOYEE_DASHBOARD.html'
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

    // Close login / apply-login / facilities modals when clicking the dark
    // area outside the box, and wire up their X buttons.
    const modalClosers = {
        'login-modal': closeLoginModal,
        'apply-login-modal': closeApplyLoginModal,
        'FacilitiesModal': closeFacilitiesModal
    };

    Object.keys(modalClosers).forEach(function (id) {
        const overlay = document.getElementById(id);
        if (!overlay) return;

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) modalClosers[id]();
        });

        overlay.querySelectorAll('[class*="close"]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                modalClosers[id]();
            });
        });
    });

    // Escape key closes every modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeLoginModal();
            closeApplyLoginModal();
            closeFacilitiesModal();
            if (typeof window.closeSignupModal === 'function') window.closeSignupModal();
            if (typeof window.closeApplyModal === 'function') window.closeApplyModal();
        }
    });
});
</script>
@endverbatim
@endsection
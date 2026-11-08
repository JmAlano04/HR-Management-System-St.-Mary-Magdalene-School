<<<<<<< HEAD

@php
    $formId = $formId ?? 'login-form';
    $emailInputId = $emailInputId ?? 'email';
    $passwordInputId = $passwordInputId ?? 'password';
@endphp

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form
        id="{{ $formId }}"
        method="POST"
        action="{{ route('login') }}"
        class="form-group"
        data-has-errors="{{ ($loginContext ?? null) === 'apply' && old('login_context') === 'apply' && $errors->hasAny(['email', 'password']) ? 'true' : 'false' }}"
    >
        @csrf
        @if (($loginContext ?? null) === 'apply')
            <input type="hidden" name="login_context" value="apply">
        @endif

        <!-- Email Address -->
        <div>
            <x-input-label :for="$emailInputId" :value="__('Email')" />
            <x-text-input :id="$emailInputId" class="block mt-1 w-full" placeholder="Enter your email address" type="email" name="email" :value="($loginContext ?? null) === 'apply' ? session('registered_email', old('email')) : old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label :for="$passwordInputId" :value="__('Password')" />

            <div class="password-input-wrap">
                <x-text-input :id="$passwordInputId" class="block mt-1 w-full"
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required autocomplete="current-password" />
                <button class="password-toggle" type="button" data-target="{{ $passwordInputId }}"
                    aria-label="Show password" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                            fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" fill="none"
                            stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <br>
            
        <!-- Remember Me -->

        <div class="form-row">
                {{-- <label for="remember_me" class="remember-me" style="display: flex" >
                    <input id="remember_me" id="apply-remember"  type="checkbox" name="remember">
                    <span >{{ __('Remember me') }}</span>
                </label> --}}
       
          
           @if (Route::has('password.request'))
                <a  class="forgot-link" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif 
        </div>
        
        <div class="flex items-center justify-end mt-4"> 
          
            <x-primary-button class="login-submit">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
=======
<style>
    /* Unique class names so the homepage's own .password-toggle script
       does not also fire on this button (that caused a double toggle). */
    .pw-wrap {
        position: relative;
        display: block;
        margin-top: 0.25rem;
    }

    .pw-wrap input {
        margin-top: 0 !important;
        padding-right: 46px !important;
        box-sizing: border-box;
    }

    .pw-eye {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        margin: 0;
        padding: 0;
        border: none;
        background: transparent;
        color: #777;
        cursor: pointer;
        z-index: 5;
    }

    .pw-eye:hover {
        color: var(--primary, #b22222);
    }

    .pw-eye svg {
        width: 20px;
        height: 20px;
        pointer-events: none;
    }
</style>

<!-- Session Status -->
<x-auth-session-status class="mb-4" :status="session('status')" />

<form method="POST" action="{{ route('login') }}" class="form-group">
    @csrf

    <!-- Email Address -->
    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input id="email" class="block mt-1 w-full" placeholder="Enter your email address" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <!-- Password -->
    <div class="mt-4">
        <x-input-label for="password" :value="__('Password')" />

        <div class="pw-wrap">
            <x-text-input id="password" class="block w-full"
                          type="password"
                          name="password"
                          placeholder="Enter your password"
                          required autocomplete="current-password" />

            <button type="button" class="pw-eye" id="loginPwEye"
                    onclick="toggleLoginPassword()"
                    aria-label="Show password" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                    <path id="loginPwSlash" d="M4 4l16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" style="display:none"/>
                </svg>
            </button>
        </div>

        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <br>

    <div class="form-row">
        {{-- Remember Me
        <label for="remember_me" class="remember-me" style="display: flex">
            <input id="remember_me" type="checkbox" name="remember">
            <span>{{ __('Remember me') }}</span>
        </label> --}}

        @if (Route::has('password.request'))
            <a class="forgot-link" href="{{ route('password.request') }}">
                {{ __('Forgot your password?') }}
            </a>
        @endif
    </div>

    <div class="flex items-center justify-end mt-4">
        <x-primary-button class="login-submit">
            {{ __('Log in') }}
        </x-primary-button>
    </div>
</form>

<script>
    function toggleLoginPassword() {
        var input = document.getElementById('password');
        var btn = document.getElementById('loginPwEye');
        var slash = document.getElementById('loginPwSlash');
        if (!input) return;

        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        slash.style.display = show ? 'inline' : 'none';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    }
</script>
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

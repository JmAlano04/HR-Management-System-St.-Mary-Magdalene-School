<!-- Session Status -->
<x-auth-session-status class="mb-4" :status="session('status')" />

<form method="POST" action="{{ route('login') }}" class="form-group">
    @csrf

    <!-- Email Address -->
    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input id="email" class="block mt-1 w-full"
                      placeholder="Enter your email address"
                      type="email" name="email" :value="old('email')"
                      required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <!-- Password -->
    <div class="mt-4">
        <x-input-label for="password" :value="__('Password')" />

        <div class="login-pw-wrap mt-1">
            <x-text-input id="password" class="block w-full"
                          type="password"
                          name="password"
                          placeholder="Enter your password"
                          required autocomplete="current-password" />

            <button type="button" class="login-pw-toggle"
                    aria-label="Show password" aria-pressed="false"
                    onclick="
                        var wrap  = this.closest('.login-pw-wrap');
                        var input = wrap.querySelector('input');
                        var show  = input.type === 'password';
                        input.type = show ? 'text' : 'password';
                        this.setAttribute('aria-pressed', show ? 'true' : 'false');
                        this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                        wrap.querySelector('.eye-open').style.display   = show ? 'block' : 'none';
                        wrap.querySelector('.eye-slash').style.display  = show ? 'none'  : 'block';
                        input.focus();
                    ">
                <!-- Eye with slash: password is hidden (same as your example) -->
                <svg class="eye-slash" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M17 17l-10-10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <!-- Open eye: password is visible -->
                <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true" style="display:none">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </button>
        </div>

        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <br>

    <div class="form-row">
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

<style>
    .login-pw-wrap { position: relative; }

    .login-pw-wrap input { padding-right: 46px !important; }

    .login-pw-toggle {
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        padding: 0;
        border: none;
        background: transparent;
        color: #777;
        cursor: pointer;
        z-index: 2;
    }

    .login-pw-toggle:hover { color: #b22222; }

    .login-pw-toggle svg { width: 20px; height: 20px; }

    /* hide the browser's built-in reveal icon (Edge) */
    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear { display: none; }
</style>
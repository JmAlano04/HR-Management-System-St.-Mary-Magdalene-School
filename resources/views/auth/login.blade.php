@php
    $formId = $formId ?? 'login-form';
    $emailInputId = $emailInputId ?? 'email';
    $passwordInputId = $passwordInputId ?? 'password';

    $isApply = ($loginContext ?? null) === 'apply';

    // Ipakita lang ang warning sa modal na pinanggalingan ng submit
    // (parehong component ang gamit ng login modal at apply-login modal)
    $hasLoginError = ($isApply ? old('login_context') === 'apply' : old('form_origin') === 'login')
        && $errors->hasAny(['email', 'password']);
@endphp

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Warning: invalid credentials -->
    @if ($hasLoginError)
        <div class="login-warning" role="alert">
            <svg class="login-warning-icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 3 2 20h20L12 3Z" fill="none" stroke="currentColor" stroke-width="1.8"
                      stroke-linejoin="round"/>
                <path d="M12 10v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <circle cx="12" cy="17" r="1" fill="currentColor"/>
            </svg>
            <div class="login-warning-text">
                <strong>Login failed</strong>
                <span>{{ $errors->first('email') ?: $errors->first('password') }}</span>
            </div>
        </div>
    @endif

    <form
        id="{{ $formId }}"
        method="POST"
        action="{{ route('login') }}"
        class="form-group"
        data-has-errors="{{ $isApply && old('login_context') === 'apply' && $errors->hasAny(['email', 'password']) ? 'true' : 'false' }}"
    >
        @csrf
        @if ($isApply)
            <input type="hidden" name="login_context" value="apply">
        @else
            {{-- Ito ang nagpapa-reopen ng login modal pagkatapos ng failed login --}}
            <input type="hidden" name="form_origin" value="login">
        @endif

        <!-- Email Address -->
        <div>
            <x-input-label :for="$emailInputId" :value="__('Email')" />
            <x-text-input :id="$emailInputId"
                class="block mt-1 w-full {{ $hasLoginError ? 'input-invalid' : '' }}"
                placeholder="Enter your email address" type="email" name="email"
                :value="$isApply ? session('registered_email', old('email')) : old('email')"
                required autofocus autocomplete="username" />
            @unless ($hasLoginError)
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            @endunless
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label :for="$passwordInputId" :value="__('Password')" />

            <div class="password-input-wrap">
                <x-text-input :id="$passwordInputId"
                    class="block mt-1 w-full {{ $hasLoginError ? 'input-invalid' : '' }}"
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

            @unless ($hasLoginError)
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            @endunless
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
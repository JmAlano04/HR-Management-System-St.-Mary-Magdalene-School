{{-- data-has-errors lets HOMEPAGEScript.js reopen the modal after a failed server validation --}}
<form
    method="POST"
    action="{{ route('register-create') }}"
    id="signup-form"
    class="signup-form"
    data-has-errors="{{ $errors->hasAny(['name', 'email', 'password', 'password_confirmation']) ? 'true' : 'false' }}"
>
    @csrf

    <!-- Full Name -->
    <div class="form-group">
        <x-input-label for="signup-name" :value="__('Full Name')" />

        <x-text-input
            id="signup-name"
            class="block mt-1 w-full"
            type="text"
            name="name"
            placeholder="Enter your full name"
            :value="old('name')"
            required
            autocomplete="name"
        />

        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <!-- Email Address -->
    <div class="form-group">
        <x-input-label for="signup-email" :value="__('Email Address')" />

        <x-text-input
            id="signup-email"
            class="block mt-1 w-full"
            type="email"
            name="email"
            placeholder="Enter your email address"
            :value="old('email')"
            required
            autocomplete="username"
        />

        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <!-- Password -->
    <div class="form-group">
        <x-input-label for="signup-password" :value="__('Password')" />

        <div class="password-input-wrap">
            <x-text-input
                id="signup-password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                placeholder="Create a password"
                required
                autocomplete="new-password"
            />

           {{-- Eye icon is written here so it always shows; the JS swaps it on click --}}
            <button
                class="password-toggle"
                type="button"
                data-target="signup-password"
                aria-label="Show password"
                aria-pressed="false"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                          fill="none" stroke="currentColor" stroke-width="1.8"
                          stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M17 17l-10-10"
                          fill="none" stroke="currentColor" stroke-width="1.8"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

        </div>

        <x-input-error :messages="$errors->get('password')" class="mt-2" />

        <!-- Password Requirements -->
        <div id="password-requirements" class="password-requirements mt-3" aria-live="polite">
            <div class="pr-header">
                <strong>Password requirements</strong>
                <span id="password-count">(0/4)</span>
            </div>

            <div class="requirement" data-requirement="length">
                <span class="dot"></span>
                <span class="text">Minimum 12 characters</span>
            </div>

            <div class="requirement" data-requirement="capital">
                <span class="dot"></span>
                <span class="text">One capitalized letter</span>
            </div>

            <div class="requirement" data-requirement="number">
                <span class="dot"></span>
                <span class="text">One number</span>
            </div>

            <div class="requirement" data-requirement="symbol">
                <span class="dot"></span>
                <span class="text">At least one special character (e.g. !@#$%^&*)</span>
            </div>
        </div>
    </div>

    <!-- Confirm Password -->
    <div class="form-group">
        <x-input-label for="signup-confirm-password" :value="__('Confirm Password')" />

        <div class="password-input-wrap">
            <x-text-input
                id="signup-confirm-password"
                class="block mt-1 w-full"
                type="password"
                name="password_confirmation"
                placeholder="Confirm your password"
                required
                autocomplete="new-password"
            />

              <button
                class="password-toggle"
                type="button"
                data-target="signup-confirm-password"
                aria-label="Show password"
                aria-pressed="false"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                          fill="none" stroke="currentColor" stroke-width="1.8"
                          stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M17 17l-10-10"
                          fill="none" stroke="currentColor" stroke-width="1.8"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />

        <span id="signup-error-message" class="error-text" hidden>
            Passwords do not match
        </span>
    </div>

    <!-- Submit -->
    <div class="form-group mt-4">
        <x-primary-button class="login-submit w-full justify-center">
            {{ __('Submit') }}
        </x-primary-button>
    </div>
</form>
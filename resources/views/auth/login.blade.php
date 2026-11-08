
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


    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="form-group">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full"  placeholder="Enter your email address" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            required autocomplete="current-password" />

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


<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
<<<<<<< HEAD
use App\Models\Banner;
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
<<<<<<< HEAD
        return view('welcome', [
            'homepageBannerSlides' => Banner::slidesFor('homepage'),
        ]);
=======
        return view('welcome');
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    /**
     * Handle an incoming authentication request.
     */
<<<<<<< HEAD
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->hasRole('Admin')) {
            $home = route('dashboard', absolute: false);
        } elseif ($user->hasRole('HR')) {
            $home = route('dashboard', absolute: false);
        } elseif ($user->hasRole('Employee')) {
            $home = route('employee-dashboard', absolute: false);
        } elseif ($user->hasRole('Accounting')) {
            $home = route('dashboard', absolute: false);
        } elseif ($user->hasRole('Applicant')) {
            $home = route('dashboard', absolute: false);
        } else {
            // Walang role: huwag papasukin
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has no role assigned. Please contact HR.',
            ]);
        }

        return redirect($home);
    }

=======
  public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $request->session()->regenerate();

    $user = $request->user();

    if ($user->hasRole('Admin')) {
        $home = route('dashboard', absolute: false);
    } elseif ($user->hasRole('HR')) {
        $home = route('dashboard', absolute: false);
    } elseif ($user->hasRole('Employee')) {
        $home = route('employee-dashboard', absolute: false);
    }elseif ($user->hasRole('Accounting')) {
        $home = route('dashboard', absolute: false);
    } elseif ($user->hasRole('Applicant')) {
        $home = route('dashboard', absolute: false);
    } else {
        // Walang role: huwag papasukin
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Your account has no role assigned. Please contact HR.',
        ]);
    }

    return redirect()->intended($home);
}

>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

<<<<<<< HEAD
        return redirect()->intended(route('home', absolute: false));
=======
            return redirect()->intended(route('home', absolute: false));
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }
}

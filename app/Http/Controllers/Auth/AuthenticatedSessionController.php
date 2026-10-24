<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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
        return view('welcome');
    }

    /**
     * Handle an incoming authentication request.
     */
  public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $request->session()->regenerate();

    $user = $request->user();

    if ($user->hasRole('Employee')) {
         $home = route('employee-dashboard', absolute: false);
    } elseif ($user->hasRole('HR')) {
        $home = route('dashboard', absolute: false);
    } elseif ($user->hasRole('Accounting')) {
        $home = route('dashboard', absolute: false);
    } elseif ($user->hasRole('')) {
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

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

            return redirect()->intended(route('home', absolute: false));
    }
}

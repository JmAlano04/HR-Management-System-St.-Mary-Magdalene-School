<?php

namespace App\Http\Controllers\Auth;

use App\Models\Banner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * /login renders the home page with the login modal open.
     */
    public function create(): View
    {
        return view('welcome', [
            'homepageBannerSlides' => Banner::slidesFor('homepage'),
            'openLogin' => true,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Block unverified accounts: log them out and reopen the login modal
        // with the status message (the home page opens the modal when 'status' is set).
        if (! $user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')
                ->with('status', 'Your email address is not verified. Please check your inbox for the verification email.');
        }

        if ($user->hasRole('Employee')) {
            $home = route('employee-dashboard', absolute: false);
        } elseif ($user->hasAnyRole(['Admin', 'HR', 'Accounting', 'Applicant'])) {
            $home = route('dashboard', absolute: false);
        } else {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')
                ->withInput(['email' => $request->input('email'), 'form_origin' => 'login'])
                ->withErrors([
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

        return redirect()->route('home');
    }
}
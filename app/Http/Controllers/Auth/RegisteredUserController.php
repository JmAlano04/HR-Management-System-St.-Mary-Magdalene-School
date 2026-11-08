<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
<<<<<<< HEAD
use App\Models\Banner;
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
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
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

<<<<<<< HEAD
        $user->assignRole('Applicant');

        return redirect()
            ->route('login')
            ->with('registration_success', true)
            ->with('registered_email', $user->email);
=======
        // Auth::login($user);
        $assignRole = $user->assignRole('applicant');
        
        
          return redirect()->route('login')
        ->with('success', 'Registration successful! Please log in.');
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OffboardingController extends Controller
{
    //
    public function index ()
    {
        return view ('offboarding');
    }
}

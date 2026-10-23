<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class EmployeeTimeCorrectionsController extends Controller
{
    //
    public function index():View
    {
        return view ('timeCorrection');
    }
}

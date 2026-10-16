<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PayrollController extends Controller
{
    //
    public function index()
    {
        return view ('payroll');
    }
}

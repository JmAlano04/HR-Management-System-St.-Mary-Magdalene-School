<?php

namespace App\Http\Controllers;


use Illuminate\View\View;

class PayrollProcessingController extends Controller
{
    
    public function index(): View
    {
     return view('payroll-processing');  
    }



}
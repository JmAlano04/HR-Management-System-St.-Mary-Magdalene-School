<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    public function index()
    {
        return view('application-form', [
            'application' => Application::where('user_id', Auth::id())->first(),
            'user'        => Auth::user(),
        ]);
    }

    public function store(Request $request)
    {
        // One application per applicant
        if (Application::where('user_id', auth()->id())->exists()) {
            return redirect()->route('applications.index')
                ->with('error', 'You have already submitted an application.');
        }

        $data = $request->validate([
            'fullname'     => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'phone'        => 'required|digits:11',
            'home_address' => 'required|string|max:255',
            'birth_date'   => 'required|date|before:today',
            'gender'       => 'required|in:Male,Female,Other',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Divorced',
            'education'    => 'required|string|max:100',
            'job_type'     => 'required|in:teaching,non-teaching',
            'position'     => 'required|string|max:100',
            'resume'       => 'required|file|mimes:pdf|max:5120',
            'coverletter'  => 'required|file|mimes:pdf|max:5120',
            'appletter'    => 'required|file|mimes:pdf|max:5120',
        ]);

        // Stored on the private disk (not publicly accessible)
        foreach (['resume', 'coverletter', 'appletter'] as $doc) {
            $data[$doc] = $request->file($doc)->store('applications/' . auth()->id());
        }

        $data['user_id'] = auth()->id();
        $data['status']  = 'pending';

        Application::create($data);

        return redirect()->route('applications.index')->with('success', 'Application submitted!');
    }
}
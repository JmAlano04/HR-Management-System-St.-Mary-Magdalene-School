<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    // Must match the lists in the form (resources/views/application-form.blade.php)
    private const POSITIONS = [
        'teaching'     => ['Elementary Teacher', 'High School Teacher', 'Senior High School Teacher', 'Kindergarten Teacher'],
        'non-teaching' => ['Registrar', 'Guidance Counselor', 'Librarian', 'Accountant', 'Security Guard', 'Janitor'],
    ];

    private const EDUCATION = [
        'Elementary', 'High School', 'Senior High School', 'Vocational',
        'Associate Degree', "Bachelor's Degree", "Master's Degree",
    ];

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
        if (Application::where('user_id', Auth::id())->exists()) {
            return redirect()->route('applications.index')
                ->with('error', 'You have already submitted an application.');
        }

        $data = $request->validate([
            'fullname'     => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'phone'        => 'required|digits:11',
            'home_address' => 'required|string|max:255',
            'birth_date'   => 'required|date|before_or_equal:' . now()->subYears(18)->toDateString(),
            'gender'       => 'required|in:Male,Female,Other',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Divorced',
            'education'    => ['required', Rule::in(self::EDUCATION)],
            'job_type'     => 'required|in:teaching,non-teaching',
            'position'     => [
                'required',
                Rule::in(self::POSITIONS[$request->input('job_type')] ?? []),
            ],
            'resume'       => 'required|file|mimes:pdf|max:5120',
            'coverletter'  => 'required|file|mimes:pdf|max:5120',
            'appletter'    => 'required|file|mimes:pdf|max:5120',
        ], [
            'birth_date.before_or_equal' => 'You must be at least 18 years old.',
            'position.in'                => 'Please choose a position that matches the selected job type.',
        ]);

        // Use the same disk the Application model reads from
        $disk   = Application::DOCUMENT_DISK;
        $stored = [];

        try {
            foreach (array_keys(Application::DOCUMENTS) as $doc) {
                $data[$doc] = $stored[] = $request->file($doc)
                    ->store('applications/' . Auth::id(), $disk);
            }

            $data['user_id'] = Auth::id();
            $data['status']  = 'pending';

            Application::create($data);
        } catch (QueryException $e) {
            // Don't leave orphaned files if the insert fails
            Storage::disk($disk)->delete($stored);

            // Double-click / race: the unique user_id index caught a duplicate
            if (($e->errorInfo[1] ?? null) === 1062) {
                return redirect()->route('applications.index')
                    ->with('error', 'You have already submitted an application.');
            }

            throw $e;
        }

        return redirect()->route('applications.index')->with('success', 'Application submitted!');
    }
}
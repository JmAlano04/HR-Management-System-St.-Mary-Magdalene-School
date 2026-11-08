<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobOpening;
<<<<<<< HEAD
use App\Services\HrmsNotifier;
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    private const EDUCATION = [
        'Elementary', 'High School', 'Senior High School', 'Vocational',
        'Associate Degree', "Bachelor's Degree", "Master's Degree",
    ];

    public function index()
    {
        return view('application-form', [
            'application' => Application::where('user_id', Auth::id())->first(),
<<<<<<< HEAD
            'user' => Auth::user(),
=======
            'user'        => Auth::user(),
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            // Only positions that are currently open (managed from the Open Positions page)
            'jobOpenings' => JobOpening::orderBy('title')->pluck('title'),
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
<<<<<<< HEAD
            'first_name' => 'required|string|max:75',
            'middle_name' => 'nullable|string|max:75',
            'last_name' => 'required|string|max:75',
            'suffix' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'phone' => 'required|digits:11',
            'address_line' => 'required|string|max:160',
            'address_landmark' => 'nullable|string|max:160',
            'barangay' => 'required|string|max:100',
            'city_municipality' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'zip_code' => ['required', 'regex:/^\d{4}$/'],
            'birth_date' => 'required|date|before_or_equal:'.now()->subYears(18)->toDateString(),
            'gender' => 'required|in:Male,Female,Other',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Divorced',
            'education' => ['required', Rule::in(self::EDUCATION)],
            // Must be one of the currently open positions
            'position' => ['required', 'string', Rule::exists('job_openings', 'title')],
            'resume' => 'required|file|mimes:pdf|max:5120',
            'coverletter' => 'required|file|mimes:pdf|max:5120',
            'appletter' => 'required|file|mimes:pdf|max:5120',
        ], [
            'birth_date.before_or_equal' => 'You must be at least 18 years old.',
            'position.exists' => 'That position is no longer open. Please choose another one.',
        ]);

        $nameParts = array_filter([
            trim($data['first_name']),
            trim($data['middle_name'] ?? ''),
            trim($data['last_name']),
        ], fn (string $part): bool => $part !== '');
        $data['fullname'] = implode(' ', $nameParts);
        if (filled($data['suffix'] ?? null)) {
            $data['fullname'] .= ', '.trim($data['suffix']);
        }

        $data['first_name'] = trim($data['first_name']);
        $data['middle_name'] = filled($data['middle_name'] ?? null) ? trim($data['middle_name']) : null;
        $data['last_name'] = trim($data['last_name']);
        $data['suffix'] = filled($data['suffix'] ?? null) ? trim($data['suffix']) : null;

        $data['home_address'] = implode(', ', array_filter([
            trim($data['address_line']),
            trim($data['address_landmark'] ?? ''),
            trim($data['barangay']),
            trim($data['city_municipality']),
            trim($data['province']),
            trim($data['zip_code']),
        ], fn (string $part): bool => $part !== ''));
        unset(
            $data['address_line'],
            $data['address_landmark'],
            $data['barangay'],
            $data['city_municipality'],
            $data['province'],
            $data['zip_code'],
        );

        // Use the same disk the Application model reads from
        $disk = Application::DOCUMENT_DISK;
=======
            'fullname'     => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'phone'        => 'required|digits:11',
            'home_address' => 'required|string|max:255',
            'birth_date'   => 'required|date|before_or_equal:' . now()->subYears(18)->toDateString(),
            'gender'       => 'required|in:Male,Female,Other',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Divorced',
            'education'    => ['required', Rule::in(self::EDUCATION)],
            // Must be one of the currently open positions
            'position'     => ['required', 'string', Rule::exists('job_openings', 'title')],
            'resume'       => 'required|file|mimes:pdf|max:5120',
            'coverletter'  => 'required|file|mimes:pdf|max:5120',
            'appletter'    => 'required|file|mimes:pdf|max:5120',
        ], [
            'birth_date.before_or_equal' => 'You must be at least 18 years old.',
            'position.exists'            => 'That position is no longer open. Please choose another one.',
        ]);

        // Use the same disk the Application model reads from
        $disk   = Application::DOCUMENT_DISK;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $stored = [];

        try {
            foreach (array_keys(Application::DOCUMENTS) as $doc) {
<<<<<<< HEAD
                $file = $request->file($doc);
                $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                $originalName = preg_replace('/[\x00-\x1F\x7F]/', '', $originalName) ?: 'document.pdf';

                $data[$doc] = $stored[] = $file->store('applications/'.Auth::id(), $disk);
                $data[$doc.'_original_name'] = $originalName;
            }

            $data['user_id'] = Auth::id();
            $data['job_type'] = str_contains(strtolower($data['position']), 'teach')
                ? 'Teaching'
                : 'Staff';
            $data['status'] = 'pending';

            $application = Application::create($data);
=======
                $data[$doc] = $stored[] = $request->file($doc)
                    ->store('applications/' . Auth::id(), $disk);
            }

            $data['user_id'] = Auth::id();
            $data['status']  = 'pending';

            Application::create($data);
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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

<<<<<<< HEAD
        HrmsNotifier::newApplicant($application);

        return redirect()->route('applications.index')->with('success', 'Application submitted!');
    }
}
=======
        return redirect()->route('applications.index')->with('success', 'Application submitted!');
    }
}
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

<?php

namespace App\Http\Controllers;

use App\Models\JobOpening;
use Illuminate\Http\Request;

class JobOpeningController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255', 'unique:job_openings,title'],
        ], [
            'title.unique' => 'That position already exists.',
        ]);

        JobOpening::create(['title' => trim($data['title'])]);

        return back()->with('success', 'Job opening added.');
    }

    public function destroy(JobOpening $jobOpening)
    {
        $jobOpening->delete();

        return back()->with('success', 'Job opening removed.');
    }
}
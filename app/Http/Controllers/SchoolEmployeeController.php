<?php

namespace App\Http\Controllers;

use App\Models\PayrollStep;
use App\Models\SchoolEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolEmployeeController extends Controller
{
    /** School employee directory (view only, sensitive data masked). */
    public function index(Request $request)
    {
        $authId = Auth::id();

        $employees = SchoolEmployee::query()
            ->excludeInternalStaff()
            // Hide the logged-in user's own employee record.
            // whereNull keeps records that have no linked account.
            ->where(fn ($q) => $q
                ->whereNull('user_id')
                ->orWhere('user_id', '!=', $authId))
            // Search + department filters run in the database (see SchoolEmployee::scopeFilter)
            ->filter($request->only(['search', 'department', 'role_type']))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        // payroll_steps: step_no => "Step 1" (name only; the daily wage is never sent to this page)
        $stepNames = PayrollStep::ordered()->pluck('name', 'step_no');

        // Keyed by id for the details modal; only masked values reach the browser
        $cards = $employees->getCollection()->mapWithKeys(fn (SchoolEmployee $e) => [
            $e->id => [
                'id'                => $e->id,
                'initials'          => $e->initials,
                'name'              => $e->name,
                'employee_id'       => $e->employee_id,
                'position'          => $e->position,
                'department_label'  => $e->department_label,
                'role_label'        => $e->role_type, // stored value is already the label
                'step_label'        => $stepNames->get($e->step_no), // e.g. "Step 3"
                'email'             => $e->masked_email,
                'birthday_label'    => $e->masked_birthday,
                'emergency_contact' => $e->masked_emergency_contact,
            ],
        ]);

        return view('school-employee', [
            'employees'         => $employees,
            'cards'             => $cards,
            'filterDepartments' => SchoolEmployee::departmentsInDatabase(),
            'roleTypes'         => SchoolEmployee::roleTypesInDatabase(),
        ]);
    }
}
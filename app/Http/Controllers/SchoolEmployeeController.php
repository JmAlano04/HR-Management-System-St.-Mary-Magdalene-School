<?php

namespace App\Http\Controllers;

use App\Models\PayrollStep;
use App\Models\SchoolEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolEmployeeController extends Controller
{
    /**
     * School employee directory (view only, sensitive data masked).
     */
    public function index(Request $request)
    {
        $authId = Auth::id();

        $employees = SchoolEmployee::query()
            ->excludeInternalStaff()

            // Hide the logged-in user's own employee record.
            ->where(function ($q) use ($authId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', '!=', $authId);
            })

            // Search employee name ONLY.
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));

                $query->where('name', 'LIKE', '%' . $search . '%');
            })

            // Department filter.
            ->when($request->filled('department'), function ($query) use ($request) {
                $query->where('department', $request->input('department'));
            })

            // Role Type filter.
            ->when($request->filled('role_type'), function ($query) use ($request) {
                $query->where('role_type', $request->input('role_type'));
            })

            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        // Payroll step names only.
        $stepNames = PayrollStep::ordered()
            ->pluck('name', 'step_no');

        // Details modal data.
        $cards = $employees->getCollection()->mapWithKeys(
            fn (SchoolEmployee $e) => [
                $e->id => [
                    'id'                => $e->id,
                    'initials'          => $e->initials,
                    'name'              => $e->name,
                    'employee_id'       => $e->employee_id,
                    'position'          => $e->position,
                    'department_label'  => $e->department_label,
                    'role_label'        => $e->role_type,
                    'step_label'        => $stepNames->get($e->step_no),
                    'email'             => $e->masked_email,
                    'birthday_label'    => $e->masked_birthday,
                    'emergency_contact' => $e->masked_emergency_contact,
                ],
            ]
        );

        return view('school-employee', [
            'employees'         => $employees,
            'cards'             => $cards,
            'filterDepartments' => SchoolEmployee::departmentsInDatabase(),
            'roleTypes'         => SchoolEmployee::roleTypesInDatabase(),
        ]);
    }
}

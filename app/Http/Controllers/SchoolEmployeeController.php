<?php

namespace App\Http\Controllers;

use App\Models\SchoolEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchoolEmployeeController extends Controller
{
    /**
     * Allowed role types. The stored value and the label are the same text,
     * so they match the options in the Role Type filter of the view.
     */
    private const ROLE_TYPES = [
        'Administrative Staff' => 'Administrative Staff',
        'Teaching Staff'       => 'Teaching Staff',
        'Maintenance Staff'    => 'Maintenance Staff',
        'Support Staff'        => 'Support Staff',
    ];

    /** School employee directory (view only, sensitive data masked). */
    public function index(Request $request)
    {
        $authId = Auth::id();

        $employees = SchoolEmployee::query()
            // Hide the logged-in user's own employee record.
            // whereNull keeps records that have no linked account.
            ->where(fn ($q) => $q
                ->whereNull('user_id')
                ->orWhere('user_id', '!=', $authId))
            // Search + department filters run in the database (see SchoolEmployee::scopeFilter)
            ->filter($request->only(['search', 'department']))
            // Role type filter is applied here so it uses the four role types above
            ->when(array_key_exists((string) $request->role_type, self::ROLE_TYPES),
                fn ($q) => $q->where('role_type', $request->role_type))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

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
                'email'             => $e->masked_email,
                'birthday_label'    => $e->masked_birthday,
                'emergency_contact' => $e->masked_emergency_contact,
            ],
        ]);

        return view('school-employee', [
            'employees'         => $employees,
            'cards'             => $cards,
            'filterDepartments' => SchoolEmployee::departmentsInDatabase(),
            'roleTypes'         => self::ROLE_TYPES,
        ]);
    }
}
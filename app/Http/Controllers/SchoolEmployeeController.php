<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolEmployee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search'     => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', Rule::in(array_keys(SchoolEmployee::DEPARTMENTS))],
            'role_type'  => ['nullable', Rule::in(array_keys(SchoolEmployee::ROLE_TYPES))],
        ]);

        $employees = SchoolEmployee::query()
            ->filter($filters)
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        // Masked data for the details modal, keyed by id (Alpine reads employees[id])
        $cards = $employees->getCollection()->mapWithKeys(fn (SchoolEmployee $e) => [
            $e->id => [
                'id'                => $e->id,
                'initials'          => $e->initials,
                'name'              => $e->name,
                'employee_id'       => $e->employee_id,
                'position'          => $e->position,
                'department_label'  => $e->department_label,
                'role_label'        => $e->role_label,
                'email'             => $e->masked_email,
                'birthday_label'    => $e->masked_birthday,
                'emergency_name'    => $e->emergency_name,
                'emergency_contact' => $e->masked_emergency_contact,
            ],
        ]);

        return view('school-employee', compact('employees', 'cards'));
    }
}
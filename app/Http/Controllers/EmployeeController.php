<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /** Directory with search + department + role filters (all done in the database). */
    public function index(Request $request)
    {
        $employees = Employee::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . trim($request->search) . '%';
                $q->where(fn ($w) => $w
                    ->where('name', 'like', $term)
                    ->orWhere('employee_id', 'like', $term)
                    ->orWhere('position', 'like', $term));
            })
            ->when(array_key_exists((string) $request->department, Employee::DEPARTMENTS),
                fn ($q) => $q->where('department', $request->department))
            ->when(array_key_exists((string) $request->role_type, Employee::ROLE_TYPES),
                fn ($q) => $q->where('role_type', $request->role_type))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('employees', [
            'employees' => $employees,
            // keyed by id so the modal can look an employee up instantly
            'cards'     => $employees->getCollection()->map->toCard()->keyBy('id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        if (blank($data['employee_id'] ?? null)) {
            $data['employee_id'] = $this->generateEmployeeId();
        }

        Employee::create($data);

        return redirect()->route('employees.index')->with('success', 'Employee added.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate($this->rules($employee));

        if (blank($data['employee_id'] ?? null)) {
            $data['employee_id'] = $employee->employee_id;
        }

        $employee->update($data);

        return redirect()->back()->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted.');
    }

    private function rules(?Employee $employee = null): array
    {
        return [
            'employee_id'       => ['nullable', 'string', 'max:30', Rule::unique('employees', 'employee_id')->ignore($employee)],
            'name'              => ['required', 'string', 'max:150'],
            'position'          => ['required', 'string', 'max:100'],
            'department'        => ['required', Rule::in(array_keys(Employee::DEPARTMENTS))],
            'role_type'         => ['required', Rule::in(array_keys(Employee::ROLE_TYPES))],
            'email'             => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee)],
            'birthday'          => ['required', 'date', 'before:today'],
            'address'           => ['required', 'string', 'max:255'],
            'emergency_name'    => ['required', 'string', 'max:150'],
            'emergency_contact' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
        ];
    }

    private function generateEmployeeId(): string
    {
        do {
            $id = 'EMP-' . random_int(100000, 999999);
        } while (Employee::where('employee_id', $id)->exists());

        return $id;
    }
}
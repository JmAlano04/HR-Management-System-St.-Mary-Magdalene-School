<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    /** Spatie role of the accounts HR can pick when adding an employee. */
    private const APPLICANT_ROLE = 'Applicant';

    /** Role the account gets once it becomes an employee. */
    private const EMPLOYEE_ROLE = 'Employee';

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

        // NEW: applicants who can still be turned into an employee
        // (Applicant role + no employee record yet), keyed by id for the form dropdown
        $applicants = User::role(self::APPLICANT_ROLE)
            ->whereDoesntHave('employee')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        return view('employees', [
            'employees'  => $employees,
            // keyed by id so the modal can look an employee up instantly
            'cards'      => $employees->getCollection()->map->toCard()->keyBy('id'),
            'applicants' => $applicants,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules() + [
            // NEW: the account must be an applicant and not yet linked to an employee
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id'),
                Rule::unique('employees', 'user_id'),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! User::role(self::APPLICANT_ROLE)->whereKey($value)->exists()) {
                        $fail('The selected account is not an applicant.');
                    }
                },
            ],
        ]);

        // NEW: name and email always come from the account, never from the browser
        $user = User::findOrFail($data['user_id']);
        $data['name']  = $user->name;
        $data['email'] = $user->email;

        if (blank($data['employee_id'] ?? null)) {
            $data['employee_id'] = $this->generateEmployeeId();
        }

        // Create the employee and switch the role together: both succeed or neither does
        DB::transaction(function () use ($data, $user) {
            Employee::create($data);

            // Applicant -> Employee (other roles of the account are kept)
            Role::findOrCreate(self::EMPLOYEE_ROLE, 'web');
            $user->removeRole(self::APPLICANT_ROLE);
            $user->assignRole(self::EMPLOYEE_ROLE);
        });

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
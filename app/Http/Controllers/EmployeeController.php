<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    /** Spatie role of the accounts HR can pick when adding an employee. */
    private const APPLICANT_ROLE = 'Applicant';

    /** Role the account gets once it becomes an employee. */
    private const EMPLOYEE_ROLE = 'Employee';

    /** Fallback employment status (column is NOT NULL, no DB default). */
    private const DEFAULT_EMPLOYMENT_STATUS = 'regular';

    /**
     * Allowed role types. The stored value and the label are the same text,
     * so they match the options in the Role Type select of the view.
     */
    private const ROLE_TYPES = [
        'Administrative Staff',
        'Teaching Staff',
        'Maintenance Staff',
        'Support Staff',
    ];

    /** Allowed employment statuses: stored value => label. */
    private const EMPLOYMENT_STATUSES = [
        'regular'     => 'Regular',
        'non regular' => 'Non-Regular',
    ];

    /** Directory with search + department + role filters (all done in the database). */
    public function index(Request $request)
    {
        $authId = Auth::id();

        // Departments that actually exist in the database (value => label)
        $filterDepartments = $this->departmentsInDatabase();

        $employees = Employee::query()
            // Itago ang employee record ng naka-login na user.
            // Kasama ang whereNull para hindi mawala ang mga walang user_id.
            ->where(fn ($q) => $q
                ->whereNull('user_id')
                ->orWhere('user_id', '!=', $authId))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . trim($request->search) . '%';
                $q->where(fn ($w) => $w
                    ->where('name', 'like', $term)
                    ->orWhere('employee_id', 'like', $term)
                    ->orWhere('position', 'like', $term));
            })
            // Department filter depends on the values stored in SQL
            ->when($filterDepartments->has((string) $request->department),
                fn ($q) => $q->where('department', $request->department))
            // Role type filter: Administrative / Teaching / Maintenance / Support Staff
            ->when(in_array((string) $request->role_type, self::ROLE_TYPES, true),
                fn ($q) => $q->where('role_type', $request->role_type))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        // Applicants who can still be turned into an employee
        // (Applicant role + no employee record yet), keyed by id for the form dropdown
        $applicants = User::role(self::APPLICANT_ROLE)
            ->whereDoesntHave('employee')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        return view('employees', [
            'employees'          => $employees,
            // keyed by id so the modal can look an employee up instantly.
            // Galing lang sa na-filter na $employees, kaya hindi lalabas
            // ang data ng naka-login na user sa JS.
            // employment_status is added here so the modal and the edit form always have it.
            'cards'              => $employees->getCollection()
                ->map(fn (Employee $e) => array_merge($e->toCard(), [
                    'employment_status' => $e->employment_status,
                ]))
                ->keyBy('id'),
            'applicants'         => $applicants,
            'employmentStatuses' => self::EMPLOYMENT_STATUSES,
            'filterDepartments'  => $filterDepartments,        // only departments found in SQL
            'formDepartments'    => $this->departmentOptions(), // standard list + SQL values
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules() + [
            // The account must be an applicant and not yet linked to an employee
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

        // Name and email always come from the account, never from the browser
        $user = User::findOrFail($data['user_id']);
        $data['name']  = $user->name;
        $data['email'] = $user->email;

        if (blank($data['employee_id'] ?? null)) {
            $data['employee_id'] = $this->generateEmployeeId();
        }

        // employment_status is NOT NULL in the database
        $data['employment_status'] ??= self::DEFAULT_EMPLOYMENT_STATUS;

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
        $this->abortIfOwnRecord($employee);

        $data = $request->validate($this->rules($employee));

        if (blank($data['employee_id'] ?? null)) {
            $data['employee_id'] = $employee->employee_id;
        }

        $employee->update($data);

        return redirect()->back()->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->abortIfOwnRecord($employee);

        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted.');
    }

    /** Hindi puwedeng galawin ng user ang sarili niyang employee record. */
    private function abortIfOwnRecord(Employee $employee): void
    {
        abort_if(
            $employee->user_id !== null && $employee->user_id === Auth::id(),
            403,
            'You cannot modify your own employee record.'
        );
    }

    private function rules(?Employee $employee = null): array
    {
        return [
            'employee_id'       => ['nullable', 'string', 'max:30', Rule::unique('employees', 'employee_id')->ignore($employee)],
            'name'              => ['required', 'string', 'max:150'],
            'position'          => ['required', 'string', 'max:100'],
            'department'        => ['required', Rule::in($this->departmentOptions()->keys()->all())],
            'role_type'         => ['required', Rule::in(self::ROLE_TYPES)],
            'employment_status' => ['required', Rule::in(array_keys(self::EMPLOYMENT_STATUSES))],
            'email'             => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee)],
            'birthday'          => ['required', 'date', 'before:today'],
            'address'           => ['required', 'string', 'max:255'],
            'emergency_name'    => ['required', 'string', 'max:150'],
            'emergency_contact' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
        ];
    }

    /**
     * Distinct departments currently stored in the database.
     * Returns [value => label]; falls back to a readable label when the
     * value is not in Employee::DEPARTMENTS.
     */
    private function departmentsInDatabase(): Collection
    {
        return Employee::query()
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->mapWithKeys(fn ($value) => [
                $value => Employee::DEPARTMENTS[$value] ?? Str::headline($value),
            ]);
    }

    /** Standard departments + any extra value already stored in the database. */
    private function departmentOptions(): Collection
    {
        return collect(Employee::DEPARTMENTS)->union($this->departmentsInDatabase());
    }

    private function generateEmployeeId(): string
    {
        do {
            $id = 'EMP-' . random_int(100000, 999999);
        } while (Employee::where('employee_id', $id)->exists());

        return $id;
    }
}
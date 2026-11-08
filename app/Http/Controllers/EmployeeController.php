<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Models\Application;
use App\Models\Employee;
use App\Models\PayrollStep;
use App\Models\SchoolEmployee;
=======
use App\Models\Employee;
use App\Models\PayrollStep;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
<<<<<<< HEAD
use Illuminate\Support\Facades\Hash;
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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

    /** Fallback payroll step (Step 1). */
    private const DEFAULT_STEP_NO = 1;

<<<<<<< HEAD
    /** Initial password assigned when an applicant becomes an employee. */
    private const DEFAULT_EMPLOYEE_PASSWORD = 'password123';

=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
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
<<<<<<< HEAD
        'non regular' => 'Probationary',
=======
        'non regular' => 'Non-Regular',
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    ];

    /** Directory with search + department + role filters (all done in the database). */
    public function index(Request $request)
    {
        $authId = Auth::id();

        // Departments that actually exist in the database (value => label)
        $filterDepartments = $this->departmentsInDatabase();
<<<<<<< HEAD
        $filterRoleTypes = SchoolEmployee::roleTypesInDatabase();
        $search = $request->query('search');
        $search = is_string($search) ? trim($search) : '';
        $department = $request->query('department');
        $department = is_string($department) ? $department : '';
        $roleType = $request->query('role_type');
        $roleType = is_string($roleType) ? $roleType : '';

        $employees = Employee::query()
            ->excludeInternalStaff()
=======

        $employees = Employee::query()
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            // Itago ang employee record ng naka-login na user.
            // Kasama ang whereNull para hindi mawala ang mga walang user_id.
            ->where(fn ($q) => $q
                ->whereNull('user_id')
                ->orWhere('user_id', '!=', $authId))
<<<<<<< HEAD
            ->when($search !== '', function ($q) use ($search) {
                $term = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)) . '%';
                $q->where(fn ($w) => $w
                    ->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("LOWER(employee_id) LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("LOWER(position) LIKE ? ESCAPE '!'", [$term]));
            })
            // Department filter depends on the values stored in SQL
            ->when($filterDepartments->has($department),
                fn ($q) => $q->where('department', $department))
            ->when($filterRoleTypes->has($roleType),
                fn ($q) => $q->where('role_type', $roleType))
=======
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
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

<<<<<<< HEAD
        // payroll_steps: step_no => step name
=======
        // payroll_steps: step_no => "Step 1 — ₱550.00 / day"
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        $payrollSteps = $this->payrollSteps();

        // Raw rows for the "Payroll Steps" manager modal
        $stepRows = PayrollStep::ordered()->get();

        // Applicants who can still be turned into an employee
        // (Applicant role + no employee record yet), keyed by id for the form dropdown
<<<<<<< HEAD
        $applicantUsers = User::role(self::APPLICANT_ROLE)
            ->whereDoesntHave('employee')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $applications = Application::query()
            ->whereIn('user_id', $applicantUsers->pluck('id'))
            ->get([
                'user_id', 'fullname', 'first_name', 'middle_name', 'last_name', 'suffix',
                'home_address', 'birth_date', 'gender',
            ])
            ->keyBy('user_id');
        $applicants = $applicantUsers
            ->filter(fn (User $user) => $applications->has($user->id))
            ->map(function (User $user) use ($applications) {
                $application = $applications->get($user->id);
                $address = $application
                    ? $this->splitApplicantAddress((string) $application->home_address)
                    : [];

                return [
                    'id'         => $user->id,
                    'name'       => $application?->fullname ?? $user->name,
                    'first_name' => $application?->first_name ?? '',
                    'middle_name' => $application?->middle_name ?? '',
                    'last_name'  => $application?->last_name ?? '',
                    'suffix'     => $application?->suffix ?? '',
                    'email'      => $user->email,
                    'address'    => $address,
                    'birthday'   => $application?->birth_date?->toDateString() ?? '',
                    'gender'     => $application?->gender ?? '',
                ];
            })
=======
        $applicants = User::role(self::APPLICANT_ROLE)
            ->whereDoesntHave('employee')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            ->keyBy('id');

        return view('employees', [
            'employees'          => $employees,
            'cards'              => $employees->getCollection()
                ->map(fn (Employee $e) => array_merge($e->toCard(), [
                    'employment_status' => $e->employment_status,
<<<<<<< HEAD
                    'step_no'           => (int) $e->step_no,
                    'step_label'        => (string) $payrollSteps->get($e->step_no, ''),
=======
                    'step_label'        => $payrollSteps->get($e->step_no),
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                ]))
                ->keyBy('id'),
            'applicants'         => $applicants,
            'employmentStatuses' => self::EMPLOYMENT_STATUSES,
            'payrollSteps'       => $payrollSteps,
            'stepRows'           => $stepRows,
            'filterDepartments'  => $filterDepartments,         // only departments found in SQL
<<<<<<< HEAD
            'filterRoleTypes'    => $filterRoleTypes,
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'formDepartments'    => $this->departmentOptions(), // standard list + SQL values
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
<<<<<<< HEAD
        $rules = $this->rules();
        if ($request->input('employee_source') === 'applicant') {
            $rules['email'] = ['nullable', 'email', 'max:255'];
        } elseif ($request->input('employee_source') === 'existing') {
            $rules['email'][] = Rule::unique('users', 'email');
        }

        $data = $request->validate($rules + [
            'first_name'  => ['required', 'string', 'max:75'],
            'middle_name' => ['nullable', 'string', 'max:75'],
            'last_name'   => ['required', 'string', 'max:75'],
            'suffix'      => ['nullable', 'string', 'max:20'],
            'address_line'       => ['required', 'string', 'max:120'],
            'address_landmark'   => ['nullable', 'string', 'max:120'],
            'barangay'           => ['required', 'string', 'max:80'],
            'city_municipality'  => ['required', 'string', 'max:80'],
            'province'           => ['required', 'string', 'max:80'],
            'zip_code'           => ['required', 'regex:/^\d{4}$/'],
            'employee_source' => ['required', Rule::in(['applicant', 'existing'])],
            // Applicant selections reuse their existing account; existing employees get a new account after validation.
            'user_id' => [
                'required_if:employee_source,applicant', 'nullable', 'integer',
                Rule::exists('users', 'id'),
                Rule::unique('employees', 'user_id'),
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if ($request->input('employee_source') === 'applicant'
                        && ! User::role(self::APPLICANT_ROLE)->whereKey($value)->exists()) {
=======
        $data = $request->validate($this->rules() + [
            // The account must be an applicant and not yet linked to an employee
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id'),
                Rule::unique('employees', 'user_id'),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! User::role(self::APPLICANT_ROLE)->whereKey($value)->exists()) {
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
                        $fail('The selected account is not an applicant.');
                    }
                },
            ],
        ]);

<<<<<<< HEAD
        $user = null;
        $createAccount = $data['employee_source'] === 'existing';
        $applicationAddress = null;
        if ($data['employee_source'] === 'applicant') {
            $user = User::findOrFail($data['user_id']);
            $application = Application::query()->where('user_id', $user->id)->first();
            $data['name'] = $this->composeName($data);
            $data['email'] = $this->generateEmployeeEmail(
                $user,
                $data['first_name'],
                $data['last_name'],
            );

            if ($application) {
                $data['first_name'] = $application->first_name;
                $data['middle_name'] = $application->middle_name;
                $data['last_name'] = $application->last_name;
                $data['suffix'] = $application->suffix;
                $data['birthday'] = $application->birth_date->toDateString();
                $data['gender'] = $application->gender;
                $applicationAddress = $application->home_address;
            }
        } else {
            $data['user_id'] = null;
            $data['name'] = $this->composeName($data);
        }
        $data['address'] = $applicationAddress ?? $this->composeAddress($data);
        unset($data['employee_source']);
        unset(
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['suffix'],
            $data['address_line'],
            $data['address_landmark'],
            $data['barangay'],
            $data['city_municipality'],
            $data['province'],
            $data['zip_code'],
        );
=======
        // Name and email always come from the account, never from the browser
        $user = User::findOrFail($data['user_id']);
        $data['name']  = $user->name;
        $data['email'] = $user->email;
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        if (blank($data['employee_id'] ?? null)) {
            $data['employee_id'] = $this->generateEmployeeId();
        }

        // These columns are NOT NULL in the database
        $data['employment_status'] ??= self::DEFAULT_EMPLOYMENT_STATUS;
        $data['step_no']             = (int) ($data['step_no'] ?? self::DEFAULT_STEP_NO);

<<<<<<< HEAD
        // Create the employee login and employee record together.
        DB::transaction(function () use (&$data, $user, $createAccount) {
            if ($createAccount) {
                Role::findOrCreate(self::EMPLOYEE_ROLE, 'web');
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make(self::DEFAULT_EMPLOYEE_PASSWORD),
                ]);
                $user->assignRole(self::EMPLOYEE_ROLE);
                $data['user_id'] = $user->id;
            }

            Employee::create($data);

            if ($user && ! $createAccount) {
                Role::findOrCreate(self::EMPLOYEE_ROLE, 'web');
                $user->forceFill([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make(self::DEFAULT_EMPLOYEE_PASSWORD),
                ])->save();
                $user->removeRole(self::APPLICANT_ROLE);
                $user->assignRole(self::EMPLOYEE_ROLE);
            }
        });

        return redirect()->route('employees.index')->with('success', 'Employee added successfully.');
=======
        // Create the employee and switch the role together: both succeed or neither does
        DB::transaction(function () use ($data, $user) {
            Employee::create($data);

            // Applicant -> Employee (other roles of the account are kept)
            Role::findOrCreate(self::EMPLOYEE_ROLE, 'web');
            $user->removeRole(self::APPLICANT_ROLE);
            $user->assignRole(self::EMPLOYEE_ROLE);
        });

        return redirect()->route('employees.index')->with('success', 'Employee added.');
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->abortIfOwnRecord($employee);

<<<<<<< HEAD
        $data = $request->validate($this->rules($employee) + [
            'first_name'  => ['required', 'string', 'max:75'],
            'middle_name' => ['nullable', 'string', 'max:75'],
            'last_name'   => ['required', 'string', 'max:75'],
            'suffix'      => ['nullable', 'string', 'max:20'],
            'address_line'       => ['required', 'string', 'max:120'],
            'address_landmark'   => ['nullable', 'string', 'max:120'],
            'barangay'           => ['required', 'string', 'max:80'],
            'city_municipality'  => ['required', 'string', 'max:80'],
            'province'           => ['required', 'string', 'max:80'],
            'zip_code'           => ['required', 'regex:/^\d{4}$/'],
        ]);
        $data['name'] = $this->composeName($data);
        $data['address'] = $this->composeAddress($data);
        unset($data['first_name'], $data['middle_name'], $data['last_name'], $data['suffix']);
        unset(
            $data['address_line'],
            $data['address_landmark'],
            $data['barangay'],
            $data['city_municipality'],
            $data['province'],
            $data['zip_code'],
        );
=======
        $data = $request->validate($this->rules($employee));
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120

        if (blank($data['employee_id'] ?? null)) {
            $data['employee_id'] = $employee->employee_id;
        }

        // Save the Step chosen in the form
        $data['step_no'] = (int) $data['step_no'];

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
<<<<<<< HEAD
            'position'          => ['required', 'string', 'max:100'],
            'department'        => ['required', 'string', 'max:100'],
=======
            'name'              => ['required', 'string', 'max:150'],
            'position'          => ['required', 'string', 'max:100'],
            'department'        => ['required', Rule::in($this->departmentOptions()->keys()->all())],
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'role_type'         => ['required', Rule::in(self::ROLE_TYPES)],
            'employment_status' => ['required', Rule::in(array_keys(self::EMPLOYMENT_STATUSES))],
            'step_no'           => ['required', 'integer', Rule::exists('payroll_steps', 'step_no')],
            'email'             => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee)],
            'birthday'          => ['required', 'date', 'before:today'],
<<<<<<< HEAD
            'gender'            => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
=======
            'address'           => ['required', 'string', 'max:255'],
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
            'emergency_name'    => ['required', 'string', 'max:150'],
            'emergency_contact' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
        ];
    }

    /**
<<<<<<< HEAD
     * Combine the form's name parts for the existing full-name database field.
     */
    private function composeName(array $data): string
    {
        $name = collect([
            trim($data['first_name']),
            trim($data['middle_name'] ?? ''),
            trim($data['last_name']),
        ])->filter()->implode(' ');

        return filled($data['suffix'] ?? null)
            ? $name . ', ' . trim($data['suffix'])
            : $name;
    }

    /**
     * Combine the address fields while preserving empty landmark position for edit parsing.
     */
    private function composeAddress(array $data): string
    {
        return implode(', ', [
            trim($data['address_line']),
            trim($data['address_landmark'] ?? ''),
            trim($data['barangay']),
            trim($data['city_municipality']),
            trim($data['province']),
            trim($data['zip_code']),
        ]);
    }

    private function splitApplicantAddress(string $address): array
    {
        $parts = array_map('trim', explode(', ', $address));
        $zipCode = array_pop($parts);

        if (! preg_match('/^\d{4}$/', $zipCode) || ! in_array(count($parts), [4, 5], true)) {
            return [
                'address_line' => $address,
                'address_landmark' => '',
                'barangay' => '',
                'city_municipality' => '',
                'province' => '',
                'zip_code' => '',
            ];
        }

        $hasLandmark = count($parts) === 5;

        return [
            'address_line' => $parts[0],
            'address_landmark' => $hasLandmark ? $parts[1] : '',
            'barangay' => $parts[$hasLandmark ? 2 : 1],
            'city_municipality' => $parts[$hasLandmark ? 3 : 2],
            'province' => $parts[$hasLandmark ? 4 : 3],
            'zip_code' => $zipCode,
        ];
    }

    private function generateEmployeeEmail(User $user, string $firstName, string $lastName): string
    {
        $initial = Str::lower((string) preg_replace('/[^a-z0-9]/i', '', Str::ascii($firstName)));
        $surname = Str::lower((string) preg_replace('/[^a-z0-9]/i', '', Str::ascii($lastName)));

        if ($initial === '' || $surname === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'user_id' => 'The selected applicant needs a first and last name to create an employee login.',
            ]);
        }

        $base = substr($initial, 0, 1).$surname;
        $email = $base.'@smms.edu';
        $number = 2;

        while (
            User::query()->where('email', $email)->where('id', '!=', $user->id)->exists()
            || Employee::query()->where('email', $email)->exists()
        ) {
            $email = $base.$number.'@smms.edu';
            $number++;
        }

        return $email;
    }

    /**
     * Payroll steps from the payroll_steps table (PayrollStep model).
     * Returns [step_no => "Step 1"].
=======
     * Payroll steps from the payroll_steps table (PayrollStep model).
     * Returns [step_no => "Step 1 — ₱550.00 / day"].
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
     */
    private function payrollSteps(): Collection
    {
        PayrollStep::ensureDefaults(); // creates Step 1..30 only if the table is empty

        return PayrollStep::ordered()
            ->get()
            ->mapWithKeys(fn (PayrollStep $s) => [
                $s->step_no => $s->name,
            ]);
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
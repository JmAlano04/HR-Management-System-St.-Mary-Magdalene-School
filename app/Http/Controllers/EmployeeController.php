<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public const CIVIL_STATUSES = [
        'Single',
        'Married',
        'Widowed',
        'Separated',
        'Divorced',
    ];

    public const GENDERS = [
        'Male',
        'Female',
        'Other',
    ];

    public const ROLE_TYPES = [
        'Administrative Staff',
        'Teaching Staff',
        'Maintenance Staff',
        'Support Staff',
    ];

    public const EMPLOYMENT = [
        'regular' => 'Regular',
        'non regular' => 'Probationary',
    ];

    /**
     * Display employees.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $employees = Employee::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {

                    // Search using existing database columns.
                    $w->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('suffix', 'like', "%{$search}%");
                        // ->orWhere('employee_id', 'like', "%{$search}%")
                        // ->orWhere('zkteco_user_id', 'like', "%{$search}%")
                        // ->orWhere('position', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->filled('department'),
                fn ($q) => $q->where(
                    'department',
                    $request->query('department')
                )
            )
            ->when(
                $request->filled('role_type'),
                fn ($q) => $q->where(
                    'role_type',
                    $request->query('role_type')
                )
            )

            // Do not order by the nonexistent name column.
            ->orderBy('last_name')
            ->orderBy('first_name')

            ->paginate(12)
            ->withQueryString();

        $payrollSteps = DB::table('payroll_steps')
            ->orderBy('step_no')
            ->pluck('name', 'step_no');

        // Data for details and edit modal.
        $cards = $employees->getCollection()->mapWithKeys(
            fn (Employee $e) => [
                $e->id => [
                    'id' => $e->id,

                    // Uses the dynamic name accessor in Employee model.
                    'initials' => $e->initials,
                    'name' => $e->name,

                    'first_name' => $e->first_name,
                    'middle_name' => $e->middle_name,
                    'last_name' => $e->last_name,
                    'suffix' => $e->suffix,

                    'employee_id' => $e->employee_id,
                    'zkteco_user_id' => $e->zkteco_user_id,

                    'position' => $e->position,
                    'department' => $e->department,
                    'department_label' => Employee::DEPARTMENTS[$e->department]
                        ?? $e->department,

                    'role_type' => $e->role_type,

                    'employment_status' => $e->employment_status,
                    'step_no' => $e->step_no,
                    'step_label' => $payrollSteps[$e->step_no] ?? null,

                    'email' => $e->email,

                    'birthday' => optional($e->birthday)->format('Y-m-d'),
                    'birthday_label' => optional($e->birthday)->format('F j, Y'),

                    'civil_status' => $e->civil_status,
                    'gender' => $e->gender,

                    'address' => $e->address,
                    'emergency_name' => $e->emergency_name,
                    'emergency_contact' => $e->emergency_contact,
                ],
            ]
        );

        // Applicant accounts without an employee record yet.
        // Prefilled from their latest application.
        $applicants = User::role('Applicant')
            ->whereNotIn(
                'id',
                Employee::whereNotNull('user_id')->pluck('user_id')
            )
            ->get()
            ->mapWithKeys(function (User $user) {

                $app = DB::table('applications')
                    ->where('user_id', $user->id)
                    ->latest('id')
                    ->first();

                return [
                    $user->id => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,

                        'first_name' => $app->first_name ?? null,
                        'middle_name' => $app->middle_name ?? null,
                        'last_name' => $app->last_name ?? null,
                        'suffix' => $app->suffix ?? null,

                        'birthday' => $app->birth_date ?? null,
                        'civil_status' => $app->civil_status ?? null,
                        'gender' => $app->gender ?? null,

                        'address' => $app
                            ? $this->splitAddress($app->home_address)
                            : null,
                    ],
                ];
            });

        $departments = Employee::query()
            ->distinct()
            ->orderBy('department')
            ->pluck('department', 'department');

        return view('employees', [
            'employees' => $employees,
            'cards' => $cards,
            'applicants' => $applicants,

            'payrollSteps' => $payrollSteps,
            'defaultStepNo' => $payrollSteps->keys()->first(),

            'employmentStatuses' => self::EMPLOYMENT,
            'civilStatuses' => self::CIVIL_STATUSES,
            'genders' => self::GENDERS,

            'filterDepartments' => $departments,
            'formDepartments' => $departments,

            'filterRoleTypes' => array_combine(
                self::ROLE_TYPES,
                self::ROLE_TYPES
            ),
        ]);
    }

    /**
     * Store a new employee.
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request));

        DB::transaction(function () use ($data) {

            $user = null;

            if ($data['employee_source'] === 'applicant') {

                $user = User::role('Applicant')
                    ->findOrFail($data['user_id']);

                // Applicant becomes an employee login.
                $user->syncRoles(['Employee']);
            }

            Employee::create(
                $this->payload($data, $user?->id)
            );
        });

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee added successfully.');
    }

    /**
     * Update an existing employee.
     */
    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate(
            $this->rules($request, $employee)
        );

        $employee->update(
            $this->payload($data, $employee->user_id)
        );

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Delete an employee.
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted.');
    }

    /**
     * Validation rules.
     */
    private function rules(
        Request $request,
        ?Employee $employee = null
    ): array {

        $creating = $employee === null;

        return [
            'employee_source' => $creating
                ? ['required', 'in:applicant,existing']
                : ['nullable'],

            'user_id' => [
                Rule::requiredIf(
                    $creating &&
                    $request->input('employee_source') === 'applicant'
                ),
                'nullable',
                'exists:users,id',
            ],

            'zkteco_user_id' => [
                'required',
                'digits_between:1,50',
                Rule::unique('employees', 'zkteco_user_id')
                    ->ignore($employee?->id),
            ],

            'first_name' => [
                'required',
                'string',
                'max:75',
            ],

            'middle_name' => [
                'nullable',
                'string',
                'max:75',
            ],

            'last_name' => [
                'required',
                'string',
                'max:75',
            ],

            'suffix' => [
                'nullable',
                'string',
                'max:20',
            ],

            'position' => [
                'required',
                'string',
                'max:150',
            ],

            'department' => [
                'required',
                'string',
                'max:100',
            ],

            'role_type' => [
                'required',
                Rule::in(self::ROLE_TYPES),
            ],

            'employment_status' => [
                'required',
                Rule::in(array_keys(self::EMPLOYMENT)),
            ],

            'step_no' => [
                'required',
                'integer',
                'exists:payroll_steps,step_no',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email')
                    ->ignore($employee?->id),
            ],

            'birthday' => [
                'required',
                'date',
                'before:today',
            ],

            'civil_status' => [
                'required',
                Rule::in(self::CIVIL_STATUSES),
            ],

            'gender' => [
                'required',
                Rule::in(self::GENDERS),
            ],

            'address_line' => [
                'required',
                'string',
                'max:255',
            ],

            'address_landmark' => [
                'nullable',
                'string',
                'max:255',
            ],

            'barangay' => [
                'required',
                'string',
                'max:255',
            ],

            'city_municipality' => [
                'required',
                'string',
                'max:255',
            ],

            'province' => [
                'required',
                'string',
                'max:255',
            ],

            'zip_code' => [
                'required',
                'digits:4',
            ],

            'emergency_name' => [
                'required',
                'string',
                'max:255',
            ],

            'emergency_contact' => [
                'required',
                'string',
                'max:50',
            ],
        ];
    }

    /**
     * Columns saved to the employees table.
     *
     * IMPORTANT:
     * No 'name' column is inserted here.
     * Full name is generated by the Employee model accessor.
     */
    private function payload(array $d, ?int $userId): array
    {
        $first = trim($d['first_name']);

        $middle = $this->blankToNull(
            $d['middle_name'] ?? null
        );

        $last = trim($d['last_name']);

        $suffix = $this->blankToNull(
            $d['suffix'] ?? null
        );

        return [
            'user_id' => $userId,

            'employee_id' => $d['zkteco_user_id'],
            'zkteco_user_id' => $d['zkteco_user_id'],

            // Separate name columns.
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'suffix' => $suffix,

            'position' => $d['position'],
            'department' => $d['department'],
            'role_type' => $d['role_type'],

            'employment_status' => $d['employment_status'],
            'step_no' => $d['step_no'],

            'email' => $d['email'],
            'birthday' => $d['birthday'],

            'civil_status' => $d['civil_status'],
            'gender' => $d['gender'],

            'address' => implode(', ', [
                $d['address_line'],
                $d['address_landmark'] ?? '',
                $d['barangay'],
                $d['city_municipality'],
                $d['province'],
                $d['zip_code'],
            ]),

            'emergency_name' => $d['emergency_name'],
            'emergency_contact' => $d['emergency_contact'],
        ];
    }

    /**
     * Convert empty strings to null.
     */
    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Split stored address into form fields.
     */
    private function splitAddress(?string $value): array
    {
        $parts = array_map(
            'trim',
            explode(',', (string) $value)
        );

        if (
            count($parts) === 6 &&
            preg_match('/^\d{4}$/', $parts[5])
        ) {
            return [
                'address_line' => $parts[0],
                'address_landmark' => $parts[1],
                'barangay' => $parts[2],
                'city_municipality' => $parts[3],
                'province' => $parts[4],
                'zip_code' => $parts[5],
            ];
        }

        return [
            'address_line' => (string) $value,
            'address_landmark' => '',
            'barangay' => '',
            'city_municipality' => '',
            'province' => '',
            'zip_code' => '',
        ];
    }
}

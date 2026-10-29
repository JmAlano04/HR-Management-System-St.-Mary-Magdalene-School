<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public const GENDERS = ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'];

    public const CIVIL_STATUSES = [
        'Single'    => 'Single',
        'Married'   => 'Married',
        'Widowed'   => 'Widowed',
        'Separated' => 'Separated',
        'Divorced'  => 'Divorced',
    ];

    private const PHONE_REGEX = '/^[0-9+\-\s()]{7,20}$/';

    public function edit(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('profile.edit', [
            'user'     => $request->user(),
            'employee' => $employee,
            'stepName' => $employee ? $this->stepName($employee) : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->employee) {
            return $this->updateAccount($request);
        }

        $user->fill(app(ProfileUpdateRequest::class)->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        abort_if($request->user()->employee !== null, 403);

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $user     = $request->user();
        $employee = $user->employee;

        abort_if($employee === null, 403);

        $data = $request->validate(
            $this->accountRules($user, $employee),
            $this->accountMessages(),
            $this->accountAttributes(),
        );

        $education = $this->cleanEducation($data['education'] ?? []);

        DB::transaction(function () use ($user, $employee, $data, $education) {
            $employee->update([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'birthday'          => $data['birthday'],
                'gender'            => $data['gender'] ?? null,
                'civil_status'      => $data['civil_status'] ?? null,
                'phone'             => $data['phone'] ?? null,
                'address'           => $data['address'] ?? '',
                'emergency_name'    => $data['emergency_name'] ?? '',
                'emergency_contact' => $data['emergency_contact'] ?? '',
                'education'         => $education ?: null,
                'sss_number'        => $data['sss_number'] ?? null,
                'philhealth_number' => $data['philhealth_number'] ?? null,
                'pagibig_number'    => $data['pagibig_number'] ?? null,
                'tin'               => $data['tin'] ?? null,
            ]);

            $user->fill(['name' => $data['name'], 'email' => $data['email']]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();
        });

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    private function accountRules(User $user, Employee $employee): array
    {
        $phone = ['nullable', 'string', 'regex:' . self::PHONE_REGEX];

        return [
            'name'              => ['required', 'string', 'max:120'],
            'email'             => [
                'required', 'string', 'email', 'max:254',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('employees', 'email')->ignore($employee->id),
            ],
            'birthday'          => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'gender'            => ['nullable', Rule::in(array_keys(self::GENDERS))],
            'civil_status'      => ['nullable', Rule::in(array_keys(self::CIVIL_STATUSES))],
            'phone'             => $phone,
            'address'           => ['nullable', 'string', 'max:500'],
            'emergency_name'    => ['nullable', 'string', 'max:120'],
            'emergency_contact' => $phone,
            'sss_number'        => ['nullable', 'string', 'max:32'],
            'philhealth_number' => ['nullable', 'string', 'max:32'],
            'pagibig_number'    => ['nullable', 'string', 'max:32'],
            'tin'               => ['nullable', 'string', 'max:32'],
            'education'         => ['nullable', 'array'],
        ] + $this->educationRules();
    }

    private function educationRules(): array
    {
        $rules = [];

        foreach (Employee::EDUCATION_LEVELS as $level => $config) {
            foreach ($config['fields'] as $field => [$label, $hint, $max]) {
                $rules["education.$level.$field"] = ['nullable', 'string', "max:$max"];
            }
        }

        return $rules;
    }

    private function accountMessages(): array
    {
        return [
            'birthday.before_or_equal' => 'You must be at least 18 years old.',
            'phone.regex'              => 'Use only digits, +, -, spaces or parentheses (7 to 20 characters).',
            'emergency_contact.regex'  => 'Use only digits, +, -, spaces or parentheses (7 to 20 characters).',
        ];
    }

    private function accountAttributes(): array
    {
        $attributes = [
            'phone'             => 'contact number',
            'emergency_name'    => 'emergency contact name',
            'emergency_contact' => 'emergency contact number',
            'sss_number'        => 'SSS number',
            'philhealth_number' => 'PhilHealth number',
            'pagibig_number'    => 'Pag-IBIG number',
            'tin'               => 'TIN',
        ];

        foreach (Employee::EDUCATION_LEVELS as $level => $config) {
            foreach ($config['fields'] as $field => [$label, $hint, $max]) {
                $attributes["education.$level.$field"] = mb_strtolower($config['label'] . ' ' . $label);
            }
        }

        return $attributes;
    }

    private function cleanEducation(array $education): array
    {
        return collect($education)
            ->map(fn ($fields) => collect((array) $fields)
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->filter(fn ($value) => filled($value))
                ->all())
            ->filter()
            ->all();
    }

    private function stepName(Employee $employee): ?string
    {
        $stepNo = DB::table('payroll_records')
            ->where('employee_id', $employee->id)
            ->orderByDesc('period')
            ->orderByDesc('cutoff')
            ->value('step_no');

        if ($stepNo) {
            return DB::table('payroll_steps')->where('step_no', $stepNo)->value('name') ?: "Step {$stepNo}";
        }

        return DB::table('payslips')
            ->where('employee_id', $employee->id)
            ->latest('id')
            ->value('step_name');
    }
}
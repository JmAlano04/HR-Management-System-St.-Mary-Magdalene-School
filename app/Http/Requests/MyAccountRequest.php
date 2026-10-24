<?php
// app/Http/Requests/MyAccountRequest.php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MyAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only a logged-in user that is linked to an employee record can edit.
        return $this->user()?->employee !== null;
    }

    /** Same cleanup as normalizePhoneNumber() in the old JS: 11 digits, 09xxxxxxxxx. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone'             => $this->normalizePhone($this->input('phone')),
            'emergency_contact' => $this->normalizePhone($this->input('emergency_contact')),
        ]);
    }

    public function rules(): array
    {
        $user     = $this->user();
        $employee = $user->employee;

        $rules = [
            'name'  => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'email', 'max:254',
                Rule::unique('employees', 'email')->ignore($employee->id),
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            // Must be at least 18 years old
            'birthday'     => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'gender'       => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
            'civil_status' => ['nullable', Rule::in(['Single', 'Married', 'Widowed', 'Separated', 'Divorced'])],

            'phone'   => ['nullable', 'digits:11'],
            'address' => ['nullable', 'string', 'max:500'],

            'emergency_name'    => ['nullable', 'string', 'max:120'],
            'emergency_contact' => ['nullable', 'digits:11'],

            'sss_number'        => ['nullable', 'string', 'max:32'],
            'philhealth_number' => ['nullable', 'string', 'max:32'],
            'pagibig_number'    => ['nullable', 'string', 'max:32'],
            'tin'               => ['nullable', 'string', 'max:32'],

            'education' => ['nullable', 'array'],
        ];

        // education.tertiary.course, education.senior_high.school, ...
        if (defined(Employee::class . '::EDUCATION_LEVELS')) {
            foreach (Employee::EDUCATION_LEVELS as $level => $config) {
                foreach ($config['fields'] as $field => [$label, $placeholder, $max]) {
                    $rules["education.$level.$field"] = ['nullable', 'string', "max:$max"];
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'birthday.before_or_equal' => 'You must be at least 18 years old.',
            'phone.digits'             => 'Enter exactly 11 digits, or leave blank.',
            'emergency_contact.digits' => 'Enter exactly 11 digits, or leave blank.',
            'email.unique'             => 'That email address is already in use.',
        ];
    }

    public function attributes(): array
    {
        return [
            'civil_status'      => 'civil status',
            'emergency_name'    => 'emergency contact name',
            'emergency_contact' => 'emergency contact number',
            'sss_number'        => 'SSS number',
            'philhealth_number' => 'PhilHealth number',
            'pagibig_number'    => 'Pag-IBIG number',
            'tin'               => 'TIN',
        ];
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0' . $digits;
        }

        return substr($digits, 0, 11);
    }
}
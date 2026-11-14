<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->employee !== null;
    }

    public function rules(): array
    {
        $types = [...array_keys(config('leave.types')), 'Other'];

        return [
            'leave_type'       => ['required', 'in:' . implode(',', $types)],
            'other_leave_type' => ['required_if:leave_type,Other', 'nullable', 'string', 'max:100'],
            'start_date'       => ['required', 'date'],
            'end_date'         => ['required', 'date', 'after_or_equal:start_date'],
            'reason'           => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            /** @var Employee $employee */
            $employee = $this->user()->employee;
            $service  = app(LeaveBalanceService::class);
            $balances = $service->balances($employee, Carbon::parse($this->start_date)->year);
            $type     = $this->input('leave_type');

            if ($balances['status'] === 'unknown') {
                $v->errors()->add('leave_type', 'Your employment status could not be verified. Contact HR before requesting leave.');
                return;
            }

            if ($balances['status'] === 'non-regular' && $type !== config('leave.non_regular.type')) {
                $v->errors()->add('leave_type', 'Non-regular employees may request ' . config('leave.non_regular.type') . ' only.');
                return;
            }

            $remaining = $service->remainingFor($balances, $type);
            $requested = (int) Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date)) + 1;

            if ($remaining === null) {
                $v->errors()->add('leave_type', 'No balance is configured for this leave type.');
            } elseif ($requested > $remaining) {
                $v->errors()->add('end_date', "You have {$remaining} day" . ($remaining === 1 ? '' : 's') . " remaining, but selected {$requested} days.");
            }
        });
    }
}
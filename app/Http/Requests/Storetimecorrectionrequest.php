<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->employee !== null;
    }

    public function rules(): array
    {
        $isTimeIn = $this->input('correction_type') === 'Time In';

        // Time In must be between 06:00 and 16:30 (same as the front-end min/max)
        $timeIn = $isTimeIn ? ['after_or_equal:06:00', 'before_or_equal:16:30'] : [];

        return [
            'correction_date' => ['required', 'date', 'before_or_equal:today'],
            'correction_type' => ['required', 'in:Time In,Time Out'],
            'incorrect_time'  => ['required', 'date_format:H:i', ...$timeIn],
            'correct_time'    => ['required', 'date_format:H:i', ...$timeIn],
            'note'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}
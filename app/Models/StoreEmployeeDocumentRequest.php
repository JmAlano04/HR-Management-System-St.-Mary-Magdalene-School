<?php
// app/Http/Requests/Employee/StoreEmployeeDocumentRequest.php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Must be a logged-in user that is linked to an employee record.
        return $this->user()?->employee !== null;
    }

    public function rules(): array
    {
        return [
            'submission_box_id' => ['required', 'integer', 'exists:submission_boxes,id'],
            'type'              => ['nullable', 'string', 'max:255'],
            // 10 MB (max is in KB). Matches the JS check and the accept="" list.
            'file'              => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please choose a file to upload.',
            'file.max'      => 'The selected file must be 10 MB or smaller.',
            'file.mimes'    => 'Accepted files: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG.',
        ];
    }
}
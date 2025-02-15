<?php

namespace App\Http\Requests\Invoice;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'required|exists:users,id,role,' . User::ROLE_STUDENT,
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Course cannot be empty',
            'course_id.exists' => 'Course does not exist',
            'student_ids.required' => 'Student cannot be empty',
            'student_ids.array' => 'Student must be an array',
            'student_ids.*.required' => 'Student cannot be empty',
            'student_ids.*.exists' => 'Student does not exist or is not a student',
        ];
    }
}

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
            'student_id' => 'required|exists:users,id,role,' . User::ROLE_STUDENT,
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => '课程不能为空',
            'course_id.exists' => '课程不存在',
            'student_id.required' => '学生不能为空',
            'student_id.exists' => '学生不存在或不是学生身份',
        ];
    }
}

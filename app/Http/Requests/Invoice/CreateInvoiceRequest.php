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
            'course_id.required' => '课程不能为空',
            'course_id.exists' => '课程不存在',
            'student_ids.required' => '学生不能为空',
            'student_ids.array' => '学生必须是一个数组',
            'student_ids.*.required' => '学生不能为空',
            'student_ids.*.exists' => '学生不存在或不是学生身份',
        ];
    }
}

<?php

namespace App\Http\Requests\Course;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'year_month' => ['required', 'date_format:Y-m'],
            'fee' => ['required', 'numeric', 'min:0'],
            'student_ids' => ['sometimes', 'array'],
            'student_ids.*' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', User::ROLE_STUDENT);
                }),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => '课程名称不能为空',
            'year_month.required' => '年月不能为空',
            'year_month.date_format' => '年月格式必须为 YYYY-MM',
            'fee.required' => '课程费用不能为空',
            'fee.numeric' => '课程费用必须为数字',
            'fee.min' => '课程费用不能小于0',
            'student_ids.array' => '学生ID必须为数组',
            'student_ids.*.exists' => '选择的学生不存在或不是学生身份',
        ];
    }
}

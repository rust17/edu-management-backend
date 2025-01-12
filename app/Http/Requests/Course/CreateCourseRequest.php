<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class CreateCourseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'year_month' => 'required|date_format:Y-m',
            'fee' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '课程名称不能为空',
            'name.max' => '课程名称不能超过255个字符',
            'year_month.required' => '年月不能为空',
            'year_month.date_format' => '年月格式必须为YYYY-MM',
            'fee.required' => '费用不能为空',
            'fee.numeric' => '费用必须为数字',
            'fee.min' => '费用不能小于0',
        ];
    }
}

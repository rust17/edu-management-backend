<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class ListMyCourseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'year_month' => 'nullable|date_format:Y-m',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'year_month.date_format' => '年月格式必须为YYYY-MM',
            'page.integer' => '页码必须为整数',
            'page.min' => '页码不能小于1',
            'per_page.integer' => '每页数量必须为整数',
            'per_page.min' => '每页数量不能小于1',
            'per_page.max' => '每页数量不能大于100'
        ];
    }
}

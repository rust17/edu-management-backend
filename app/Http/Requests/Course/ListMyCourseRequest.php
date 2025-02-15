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
            'per_page' => 'nullable|integer|min:1|max:100',
            'keyword' => 'nullable|string'
        ];
    }

    public function messages(): array
    {
        return [
            'year_month.date_format' => 'The year and month format must be YYYY-MM',
            'page.integer' => 'The page number must be an integer',
            'page.min' => 'The page number cannot be less than 1',
            'per_page.integer' => 'The number per page must be an integer',
            'per_page.min' => 'The number per page cannot be less than 1',
            'per_page.max' => 'The number per page cannot be greater than 100',
            'keyword.string' => 'The keyword must be a string'
        ];
    }
}

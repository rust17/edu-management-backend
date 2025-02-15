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
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Course name cannot be empty',
            'name.max' => 'Course name cannot exceed 255 characters',
            'year_month.required' => 'Year and month cannot be empty',
            'year_month.date_format' => 'Year and month format must be YYYY-MM',
            'fee.required' => 'Fee cannot be empty',
            'fee.numeric' => 'Fee must be a number',
            'fee.min' => 'Fee cannot be less than 0',
            'student_ids.required' => 'Student cannot be empty',
            'student_ids.array' => 'Student must be an array',
            'student_ids.*.exists' => 'Student does not exist',
        ];
    }
}

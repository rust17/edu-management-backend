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
            'name.required' => 'Course name cannot be empty',
            'year_month.required' => 'Year and month cannot be empty',
            'year_month.date_format' => 'Year and month format must be YYYY-MM',
            'fee.required' => 'Course fee cannot be empty',
            'fee.numeric' => 'Course fee must be a number',
            'fee.min' => 'Course fee cannot be less than 0',
            'student_ids.array' => 'Student ID must be an array',
            'student_ids.*.exists' => 'The selected student does not exist or is not a student',
        ];
    }
}

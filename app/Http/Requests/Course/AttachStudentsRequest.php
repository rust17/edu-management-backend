<?php

namespace App\Http\Requests\Course;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Traits\FailedAuthorizationTrait;

class AttachStudentsRequest extends FormRequest
{
    use FailedAuthorizationTrait;

    public function authorize(): bool
    {
        if ($this->user()->role !== User::ROLE_TEACHER) {
            $this->errorMessage = '只有教师才能关联学生到课程';
            return false;
        }

        if ($this->route('course')->teacher_id !== $this->user()->id) {
            $this->errorMessage = '您只能关联学生到自己的课程';
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id,role,' . User::ROLE_STUDENT
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => '学生不能为空',
            'student_ids.array' => '学生必须为数组',
            'student_ids.*.exists' => '选择的学生不存在或不是学生身份'
        ];
    }
}

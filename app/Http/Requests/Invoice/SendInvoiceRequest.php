<?php

namespace App\Http\Requests\Invoice;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Traits\FailedAuthorizationTrait;

class SendInvoiceRequest extends FormRequest
{
    use FailedAuthorizationTrait;

    public function authorize(): bool
    {
        $course = $this->route('course');
        // if ($course->status !== Course::STATUS_PENDING) {
        //     $this->errorMessage = '只能发送待处理的账单';
        //     return false;
        // }

        // 验证是否是该教师的课程的账单
        if ($course->teacher_id !== $this->user()->id) {
            $this->errorMessage = '您只能发送自己课程的账单';
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'student_ids' => 'required|array',
            'student_ids.*' => 'required|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => '学生ID不能为空',
            'student_ids.array' => '学生ID必须是一个数组',
            'student_ids.*.required' => '学生ID不能为空',
            'student_ids.*.exists' => '学生ID不存在',
        ];
    }
}

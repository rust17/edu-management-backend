<?php

namespace App\Http\Requests\Invoice;

use App\Models\User;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Traits\FailedAuthorizationTrait;

class SendInvoiceRequest extends FormRequest
{
    use FailedAuthorizationTrait;

    public function authorize(): bool
    {
        if ($this->user()->role !== User::ROLE_TEACHER) {
            $this->errorMessage = '只有教师才能发送账单';
            return false;
        }

        $invoice = $this->route('invoice');
        if ($invoice->status !== Invoice::STATUS_PENDING) {
            $this->errorMessage = '只能发送待处理的账单';
            return false;
        }

        // 验证是否是该教师的课程的账单
        if (!$this->user()->teacherCourses()->where('id', $invoice->course_id)->exists()) {
            $this->errorMessage = '您只能发送自己课程的账单';
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [];
    }
}

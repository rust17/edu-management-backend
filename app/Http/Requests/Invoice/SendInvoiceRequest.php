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
        //     $this->errorMessage = 'Only pending invoices can be sent';
        //     return false;
        // }

        // Verify that it is the invoice of the teacher's course
        if ($course->teacher_id !== $this->user()->id) {
            $this->errorMessage = 'You can only send invoices for your own courses';
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
            'student_ids.required' => 'Student ID cannot be empty',
            'student_ids.array' => 'Student ID must be an array',
            'student_ids.*.required' => 'Student ID cannot be empty',
            'student_ids.*.exists' => 'Student ID does not exist',
        ];
    }
}

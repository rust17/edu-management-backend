<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class OmisePayRequest extends FormRequest
{
    public function rules()
    {
        return [
            'invoice_id' => 'required|exists:invoices,id',
            'token' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'invoice_id.required' => 'Invoice ID cannot be empty',
            'invoice_id.exists' => 'Invoice does not exist',
            'token.required' => 'token cannot be empty',
        ];
    }
}

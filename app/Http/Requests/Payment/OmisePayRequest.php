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
            'invoice_id.required' => '账单ID不能为空',
            'invoice_id.exists' => '账单不存在',
            'token.required' => 'token不能为空',
        ];
    }
}

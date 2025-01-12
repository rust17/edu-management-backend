<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class ListMyInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'nullable|in:pending,paid,failed',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => '状态必须是 pending、paid 或 failed',
            'page.integer' => '页码必须为整数',
            'page.min' => '页码不能小于1',
            'per_page.integer' => '每页数量必须为整数',
            'per_page.min' => '每页数量不能小于1',
            'per_page.max' => '每页数量不能大于100'
        ];
    }
}

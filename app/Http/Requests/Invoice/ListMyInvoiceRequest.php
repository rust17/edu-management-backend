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
            'per_page' => 'nullable|integer|min:1|max:100',
            'keyword' => 'nullable|string',
            'year_month' => 'nullable|date_format:Y-m',
            'send_start' => 'nullable|date_format:Y-m-d',
            'send_end' => 'nullable|date_format:Y-m-d',
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
            'per_page.max' => '每页数量不能大于100',
            'keyword.string' => '关键词必须是字符串',
            'year_month.date_format' => '年月必须是 Y-m 格式',
            'send_start.date_format' => '发送开始时间必须是 Y-m-d 格式',
            'send_end.date_format' => '发送结束时间必须是 Y-m-d 格式',
        ];
    }
}

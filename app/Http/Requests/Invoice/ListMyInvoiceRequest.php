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
            'status.in' => 'Status must be pending, paid or failed',
            'page.integer' => 'Page number must be an integer',
            'page.min' => 'Page number cannot be less than 1',
            'per_page.integer' => 'The number per page must be an integer',
            'per_page.min' => 'The number per page cannot be less than 1',
            'per_page.max' => 'The number per page cannot be greater than 100',
            'keyword.string' => 'Keyword must be a string',
            'year_month.date_format' => 'Year and month must be in Y-m format',
            'send_start.date_format' => 'Send start time must be in Y-m-d format',
            'send_end.date_format' => 'Send end time must be in Y-m-d format',
        ];
    }
}

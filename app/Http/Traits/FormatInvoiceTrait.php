<?php

namespace App\Http\Traits;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

trait FormatInvoiceTrait
{
    /**
     * 格式化学生账单详情
     *
     * @param Invoice $invoice
     * @return array
     */
    public function formatStudentInvoiceDetail(Invoice $invoice): array
    {

        return $invoice->only([
            'id', 'course_id', 'student_id', 'amount', 'status'
        ]) + [
            'no' => $invoice->no,
            'send_at' => $invoice->sent_at,
            'paid_at' => '', //todo
            'course' => [
                'id' => $invoice->course->id,
                'name' => $invoice->course->name,
                'year_month' => $invoice->course->year_month->format('Y-m'),
                'teacher_name' => $invoice->course->teacher->name,
            ]
        ];
    }

    /**
     * 格式化教师账单列表数据
     *
     * @param LengthAwarePaginator $invoices
     * @return LengthAwarePaginator
     */
    public function formatTeacherInvoicesList(LengthAwarePaginator $invoices): LengthAwarePaginator
    {
        return $invoices->tap(function (LengthAwarePaginator $invoices) {
            $invoices->transform(function (Invoice $invoice) {
                return $invoice->only([
                    'id', 'course_id', 'student_id', 'amount', 'status'
                ]) + [
                    'send_at' => $invoice->sent_at,
                    'paid_at' => '', //todo
                    'course' => $invoice->course->only(['id', 'name']) + [
                        'year_month' => $invoice->course->year_month->format('Y-m')
                    ],
                    'student_name' => $invoice->student->name
                ];
            });
        });
    }

    /**
     * 格式化学生账单列表数据
     *
     * @param LengthAwarePaginator $invoices
     * @return LengthAwarePaginator
     */
    public function formatStudentInvoiceList(LengthAwarePaginator $invoices): LengthAwarePaginator
    {
        return $invoices->tap(function (LengthAwarePaginator $invoices) {
            $invoices->transform(function (Invoice $invoice) {
                return $invoice->only([
                    'id', 'course_id', 'student_id', 'amount', 'status'
                ]) + [
                    'send_at' => $invoice->sent_at,
                    'paid_at' => '', //todo
                    'course' => $invoice->course->only(['id', 'name']) + [
                        'year_month' => $invoice->course->year_month->format('Y-m')
                    ]
                ];
            });
        });
    }
}

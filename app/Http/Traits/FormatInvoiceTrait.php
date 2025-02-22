<?php

namespace App\Http\Traits;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

trait FormatInvoiceTrait
{
    /**
     * Format student invoice details
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
            'paid_at' => $invoice->payment?->paid_at,
            'course' => [
                'id' => $invoice->course->id,
                'name' => $invoice->course->name,
                'year_month' => $invoice->course->year_month->format('Y-m'),
                'teacher_name' => $invoice->course->teacher->name,
            ]
        ];
    }

    /**
     * Format teacher invoice list data
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
                    'paid_at' => $invoice->payment?->paid_at,
                    'course' => $invoice->course->only(['id', 'name']) + [
                        'year_month' => $invoice->course->year_month->format('Y-m')
                    ],
                    'student_name' => $invoice->student->name
                ];
            });
        });
    }

    /**
     * Format student invoice list data
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
                    'paid_at' => $invoice->payment?->paid_at,
                    'course' => $invoice->course->only(['id', 'name']) + [
                        'year_month' => $invoice->course->year_month->format('Y-m')
                    ]
                ];
            });
        });
    }
}

<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\CreateInvoiceRequest;
use App\Http\Requests\Invoice\SendInvoiceRequest;
use App\Http\Services\InvoiceService;
use App\Models\Course;
use App\Http\Traits\FormatInvoiceTrait;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use FormatInvoiceTrait;

    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Teacher create invoice
     */
    public function store(CreateInvoiceRequest $request)
    {
        $course = Course::find($request->course_id);

        if ($course->teacher_id !== $request->user()->id) {
            return $this->error('You can only create invoices for your own courses', 1, 403);
        }

        $this->invoiceService->create($course, $request->student_ids);

        return $this->success('Invoice created successfully');
    }

    /**
     * Teacher view invoice list
     */
    public function teacherInvoices(Request $request)
    {
        $invoices = $this->invoiceService
            ->getTeacherInvoicesQuery($request->user()->id, $request->all())
            ->paginate($request->input('per_page', 15));

        return $this->success('Get successfully', $this->formatTeacherInvoicesList($invoices));
    }

    /**
     * Send invoice
     */
    public function send(SendInvoiceRequest $request, Course $course)
    {
        $this->invoiceService->send($course, $request->student_ids);

        return $this->success('Invoice sent successfully');
    }
}

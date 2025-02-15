<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Services\InvoiceService;
use App\Models\Invoice;
use App\Http\Traits\FormatInvoiceTrait;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use FormatInvoiceTrait;

    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Student view invoice list
     */
    public function studentInvoices(Request $request)
    {
        $invoices = $this->invoiceService
            ->getStudentInvoicesQuery($request->user()->id, $request->all())
            ->paginate($request->input('per_page', 15));

        return $this->success('Get successfully', $this->formatStudentInvoiceList($invoices));
    }

    /**
     * Student view invoice details
     */
    public function studentInvoice(Invoice $invoice)
    {
        if ($invoice->student_id !== auth()->id()) {
            return $this->error('You do not have permission to view this invoice', 1, 403);
        }

        return $this->success('Get successfully', $this->formatStudentInvoiceDetail($invoice));
    }
}

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
     * 学生查看账单列表
     */
    public function studentInvoices(Request $request)
    {
        $invoices = $this->invoiceService
            ->getStudentInvoicesQuery($request->user()->id, $request->all())
            ->paginate($request->input('per_page', 15));

        return $this->success('获取成功', $this->formatStudentInvoiceList($invoices));
    }

    /**
     * 学生查看账单详情
     */
    public function studentInvoice(Invoice $invoice)
    {
        if ($invoice->student_id !== auth()->id()) {
            return $this->error('您没有权限查看该账单', 1, 403);
        }

        return $this->success('获取成功', $this->formatStudentInvoiceDetail($invoice));
    }
}

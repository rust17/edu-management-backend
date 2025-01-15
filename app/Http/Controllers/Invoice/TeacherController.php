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
     * 教师创建账单
     */
    public function store(CreateInvoiceRequest $request)
    {
        $course = Course::find($request->course_id);

        if ($course->teacher_id !== $request->user()->id) {
            return $this->error('您只能创建自己课程的账单', 1, 403);
        }

        $this->invoiceService->create($course, $request->student_ids);

        return $this->success('账单创建成功');
    }

    /**
     * 教师查看发票列表
     */
    public function teacherInvoices(Request $request)
    {
        $invoices = $this->invoiceService
            ->getTeacherInvoicesQuery($request->user()->id, $request->all())
            ->paginate($request->input('per_page', 15));

        return $this->success('获取成功', $this->formatTeacherInvoicesList($invoices));
    }

    /**
     * 发送账单
     */
    public function send(SendInvoiceRequest $request, Course $course)
    {
        $this->invoiceService->send($course, $request->student_ids);

        return $this->success('账单已发送');
    }
}

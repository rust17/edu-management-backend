<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\CreateInvoiceRequest;
use App\Http\Requests\Invoice\SendInvoiceRequest;
use App\Http\Requests\Invoice\ListMyInvoiceRequest;
use App\Models\Invoice;
use App\Models\Course;

class InvoiceController extends Controller
{
    /**
     * 创建账单
     *
     * @param CreateInvoiceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateInvoiceRequest $request)
    {
        $course = Course::find($request->course_id);

        if ($course->teacher_id !== $request->user()->id) {
            return $this->error('您只能创建自己课程的账单', 1, 403);
        }

        $invoice = Invoice::create([
            'course_id' => $request->course_id,
            'student_id' => $request->student_id,
            'amount' => $course->fee,
            'status' => Invoice::STATUS_PENDING
        ]);

        return $this->success(
            '账单创建成功',
            $invoice->load(['course', 'student'])
        );
    }

    /**
     * 发送账单
     *
     * @param SendInvoiceRequest $request
     * @param Invoice $invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(SendInvoiceRequest $request, Invoice $invoice)
    {
        // 这里可以添加发送通知的逻辑
        // 比如发送邮件或其他通知

        $invoice->update(['status' => Invoice::STATUS_PENDING]);

        return $this->success(
            '账单已发送',
            $invoice->load(['course', 'student'])
        );
    }

    /**
     * 查看我的账单
     *
     * @param ListMyInvoiceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function my(ListMyInvoiceRequest $request)
    {
        $query = $request->user()
            ->invoices()
            ->with(['course.teacher'])
            ->latest('id');

        // 按状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(
            $request->input('per_page', 15)
        );

        return $this->success('获取成功', $invoices);
    }
}

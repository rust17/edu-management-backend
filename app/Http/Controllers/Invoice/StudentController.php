<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\ListMyInvoiceRequest;
use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class StudentController extends Controller
{
    /**
     * 获取学生的账单列表
     *
     * @param ListMyInvoiceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function studentInvoices(ListMyInvoiceRequest $request)
    {
        $query = $request->user()
            ->invoices()
            ->whereNotNull('sent_at') // 老师发送账单后，学生才能看到
            ->with(['course'])
            ->latest('id');

        // 按状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 按课程关键词筛选
        if ($request->filled('keyword')) {
            $query->whereHas('course', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->keyword . '%');
            });
        }

        // 按课程年月筛选
        if ($request->filled('year_month')) {
            $query->whereHas('course', function ($query) use ($request) {
                $query->where('year_month', Carbon::parse($request->year_month)->startOfMonth());
            });
        }

        // 按账单发送时间筛选
        if ($request->filled('send_start') && $request->filled('send_end')) {
            $query->whereBetween('sent_at', [$request->send_start, $request->send_end]);
        }

        $invoices = $query->paginate(
            $request->input('per_page', 15)
        );

        return $this->success(
            '获取成功',
            $invoices->tap(function (LengthAwarePaginator $invoices) {
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
            })
        );
    }

    /**
     * 获取学生的账单详情
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\JsonResponse
     */
    public function studentInvoice(Invoice $invoice)
    {
        // 检查是否是自己的账单 或者 账单未发送
        if ($invoice->student_id !== auth()->id() || $invoice->sent_at === null) {
            return $this->error('您没有权限查看该账单', 1, 403);
        }

        // 加载课程和教师信息
        $invoice->load(['course.teacher']);

        return $this->success('获取成功',
            $invoice->only([
                'id', 'course_id', 'student_id', 'amount', 'status'
            ]) + [
                'no' => '', //todo
                'send_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                'paid_at' => '', //todo
                'course' => [
                    'id' => $invoice->course->id,
                    'name' => $invoice->course->name,
                    'year_month' => $invoice->course->year_month->format('Y-m'),
                    'teacher_name' => $invoice->course->teacher->name,
                ]
        ]);
    }
}

<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\CreateInvoiceRequest;
use App\Http\Requests\Invoice\SendInvoiceRequest;
use App\Http\Requests\Invoice\ListMyInvoiceRequest;
use App\Models\Invoice;
use App\Models\Course;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherController extends Controller
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

        try {
            DB::beginTransaction();

            User::query()
                ->whereIn('id', $request->student_ids)
                ->whereDoesntHave('invoices',
                    fn ($query) => $query->where('course_id', $course->id)
                )->each(
                    fn (User $user) => Invoice::create([
                    'course_id' => $course->id,
                    'student_id' => $user->id,
                    'amount' => $course->fee,
                    'status' => Invoice::STATUS_PENDING,
                    'no' => Invoice::generateNo(),
                ])
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('账单创建失败，请重试', 1, 500);
        }

        return $this->success('账单创建成功');
    }

    /**
     * 发送账单
     *
     * @param SendInvoiceRequest $request
     * @param Course $course
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(SendInvoiceRequest $request, Course $course)
    {
        // 这里可以添加发送通知的逻辑
        // 比如发送邮件或其他通知

        try {
            DB::beginTransaction();

            $now = now();
            $course->invoices->whereIn('student_id', $request->student_ids)->map(
                fn (Invoice $invoice) => $invoice->update(['sent_at' => $now])
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            return $this->error('账单发送失败，请重试', 1, 500);
        }

        return $this->success('账单已发送');
    }

    /**
     * 获取教师的账单列表
     *
     * @param ListMyInvoiceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function teacherInvoices(ListMyInvoiceRequest $request)
    {
        $query = Invoice::query()
            ->with(['course', 'student'])
            ->whereHas('course', function ($query) {
                $query->where('teacher_id', auth()->id());
            })
            ->latest('id');

        // 按状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 按课程关键词筛选
        if ($request->filled('keyword')) {
            $query->whereHas('course', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->keyword . '%');
            })->orWhereHas('student', function ($query) use ($request) {
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
            $query->whereBetween('created_at', [$request->send_start, $request->send_end]);
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
                        'send_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                        'paid_at' => '', //todo
                        'course' => $invoice->course->only(['id', 'name']) + [
                            'year_month' => $invoice->course->year_month->format('Y-m')
                        ],
                        'student_name' => $invoice->student->name
                    ];
                });
            })
        );
    }
}

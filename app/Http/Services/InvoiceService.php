<?php

namespace App\Http\Services;

use App\Models\Invoice;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class InvoiceService
{
    /**
     * 创建发票
     *
     * @param Course $course
     * @param array $studentIds
     * @return array
     * @throws Throwable
     */
    public function create(Course $course, array $studentIds): array
    {
        try {
            DB::beginTransaction();

            $invoices = [];
            foreach ($studentIds as $studentId) {
                // 检查是否已经存在发票
                if (Invoice::where('course_id', $course->id)
                    ->where('student_id', $studentId)
                    ->exists()
                ) {
                    continue;
                }

                $invoices[] = Invoice::create([
                    'course_id' => $course->id,
                    'student_id' => $studentId,
                    'amount' => $course->fee,
                    'year_month' => $course->year_month,
                    'no' => Invoice::generateNo()
                ]);
            }

            DB::commit();
            return $invoices;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 发送账单
     *
     * @param Course $course
     * @param array $studentIds
     * @return void
     */
    public function send(Course $course, array $studentIds): void
    {
        try {
            DB::beginTransaction();

            $now = now();
            $course->invoices->whereIn('student_id', $studentIds)->map(
                fn (Invoice $invoice) => $invoice->update(['sent_at' => $now])
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 获取教师发票列表的查询构建器
     *
     * @param int $teacherId
     * @param array $filters
     * @return Builder
     */
    public function getTeacherInvoicesQuery(int $teacherId, array $filters): Builder
    {
        $query = Invoice::with(['student', 'course'])
            ->whereHas('course', fn ($query) => $query->where('teacher_id', $teacherId))
            ->latest('id');

        // 按状态筛选
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 按课程关键词筛选
        if (isset($filters['keyword'])) {
            $query->whereHas('course',
                fn ($query) => $query->where('name', 'like', '%' . $filters['keyword'] . '%')
            )->orWhereHas('student',
                fn ($query) => $query->where('name', 'like', '%' . $filters['keyword'] . '%')
            );
        }

        // 按课程年月筛选
        if (isset($filters['year_month'])) {
            $query->whereHas('course',
                fn ($query) => $query->where('year_month', Carbon::parse($filters['year_month'])->startOfMonth())
            );
        }

        // 按账单发送时间筛选
        if (isset($filters['send_start']) && isset($filters['send_end'])) {
            $query->whereBetween('sent_at', [$filters['send_start'], $filters['send_end']]);
        }

        return $query->latest('id');
    }

    /**
     * 获取学生发票列表的查询构建器
     *
     * @param int $studentId
     * @param array $filters
     * @return Builder
     */
    public function getStudentInvoicesQuery(int $studentId, array $filters): Builder
    {
        $query = Invoice::where('student_id', $studentId)
            ->whereNotNull('sent_at') // 老师发送账单后，学生才能看到
            ->with(['course'])
            ->latest('id');

        // 按状态筛选
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 按课程关键词筛选
        if (isset($filters['keyword'])) {
            $query->whereHas('course',
                fn ($query) => $query->where('name', 'like', '%' . $filters['keyword'] . '%')
            );
        }

        // 按课程年月筛选
        if (isset($filters['year_month'])) {
            $query->whereHas('course',
                fn ($query) => $query->where('year_month', Carbon::parse($filters['year_month'])->startOfMonth())
            );
        }

        // 按账单发送时间筛选
        if (isset($filters['send_start']) && isset($filters['send_end'])) {
            $query->whereBetween('sent_at', [$filters['send_start'], $filters['send_end']]);
        }

        return $query->latest('id');
    }
}

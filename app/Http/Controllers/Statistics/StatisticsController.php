<?php

namespace App\Http\Controllers\Statistics;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Invoice;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * 获取教师的统计信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function teacherStatistics(Request $request)
    {
        // 获取当前教师的课程数
        $courseCount = Course::where('teacher_id', auth()->id())->count();

        // 获取当前教师所有课程的账单总数
        $invoiceCount = Invoice::whereHas('course', function ($query) {
            $query->where('teacher_id', auth()->id());
        })->count();

        return $this->success('获取成功', [
            'course_count' => $courseCount,
            'invoice_count' => $invoiceCount
        ]);
    }

    /**
     * 获取学生的统计信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function studentStatistics(Request $request)
    {
        $student = auth()->user();

        // 获取当前学生的课程数
        $courseCount = $student->studentCourses()->count();

        // 获取当前学生的待支付账单数
        $pendingInvoiceCount = Invoice::where('student_id', $student->id)
            ->where('status', Invoice::STATUS_PENDING)
            ->count();

        return $this->success('获取成功', [
            'course_count' => $courseCount,
            'pending_invoice_count' => $pendingInvoiceCount
        ]);
    }
}

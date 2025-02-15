<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Invoice;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * Get teacher's statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function teacherStatistics(Request $request)
    {
        // Get the number of courses of the current teacher
        $courseCount = Course::where('teacher_id', $request->user()->id)->count();

        // Get the total number of invoices for all courses of the current teacher
        $invoiceCount = Invoice::whereHas('course',
            fn ($query) => $query->where('teacher_id', $request->user()->id)
        )->count();

        return $this->success('Get successfully', [
            'course_count' => $courseCount,
            'invoice_count' => $invoiceCount
        ]);
    }

    /**
     * Get student statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function studentStatistics(Request $request)
    {
        // Get the number of courses for the current student
        $courseCount = $request->user()->studentCourses()->count();

        // Get the number of pending invoices for the current student
        $pendingInvoiceCount = Invoice::where('student_id', $request->user()->id)
            ->whereNotNull('sent_at') // After the teacher sends the invoice, the student can see it
            ->where('status', Invoice::STATUS_PENDING)
            ->count();

        return $this->success('Get successfully', [
            'course_count' => $courseCount,
            'pending_invoice_count' => $pendingInvoiceCount
        ]);
    }
}

<?php

namespace App\Http\Traits;

use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;

trait FormatCourseTrait
{
    /**
     * Format course details (teacher view)
     *
     * @param Course $course
     * @return array
     */
    public function formatTeacherCourseDetail(Course $course): array
    {
        return $course->only([
            'id', 'name', 'fee', 'teacher_id'
        ]) + [
            'year_month' => $course->year_month->format('Y-m'),
            'students' => $course->students->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'invoice_status' => $course->invoices->where('student_id', $student->id)->first()?->status
            ])
        ];
    }

    /**
     * Format course details (student view)
     *
     * @param Course $course
     * @return array
     */
    public function formatStudentCourseDetail(Course $course): array
    {
        $invoice = $course->invoices->where('student_id', auth()->id())->first();

        return $course->only([
            'id', 'name', 'fee', 'teacher_id'
        ]) + [
            'year_month' => $course->year_month->format('Y-m'),
            'teacher' => $course->teacher->only(['id', 'name']),
            'invoice_status' => $invoice?->status,
            'invoice_send_at' => $invoice?->sent_at,
            'invoice_id' => $invoice?->id,
            'invoice_no' => $invoice?->no,
            'paid_at' => $invoice?->payment?->paid_at
        ];
    }

    /**
     * Format teacher course list data
     *
     * @param LengthAwarePaginator $courses
     * @return LengthAwarePaginator
     */
    public function formatTeacherCoursesList(LengthAwarePaginator $courses): LengthAwarePaginator
    {
        return $courses->tap(
            fn (LengthAwarePaginator $courses) => $courses->transform(
                function (Course $course) {
                    return $course->only([
                        'id', 'name', 'fee', 'teacher_id'
                    ]) + [
                        'year_month' => $course->year_month->format('Y-m'),
                        'students' => $course->students->count()
                    ];
                }
            )
        );
    }

    /**
     * Format student course list data
     *
     * @param LengthAwarePaginator $courses
     * @return LengthAwarePaginator
     */
    public function formatStudentCoursesList(LengthAwarePaginator $courses): LengthAwarePaginator
    {
        $studentId = auth()->id();
        return $courses->tap(
            fn (LengthAwarePaginator $courses) => $courses->transform(
                function (Course $course) use ($studentId) {
                    $invoice = $course->invoices->where('student_id', $studentId)->first();
                    return $course->only([
                        'id', 'name', 'fee', 'teacher_id'
                    ]) + [
                        'teacher' => $course->teacher->only(['id', 'name']),
                        'year_month' => $course->year_month->format('Y-m'),
                        'invoice_status' => $invoice?->status,
                        'invoice_send_at' => $invoice?->sent_at,
                        'invoice_id' => $invoice?->id,
                        'invoice_no' => $invoice?->no,
                        'paid_at' => ''
                    ];
                }
            )
        );
    }
}

<?php

namespace App\Http\Traits;

use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;

trait FormatCourseTrait
{
    /**
     * 格式化课程详情（教师视图）
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
                'invoice_status' => $student->invoices->first()?->status
            ])
        ];
    }

    /**
     * 格式化课程详情（学生视图）
     *
     * @param Course $course
     * @return array
     */
    public function formatStudentCourseDetail(Course $course): array
    {
        $invoice = $course->invoices->first();

        return $course->only([
            'id', 'name', 'fee', 'teacher_id'
        ]) + [
            'year_month' => $course->year_month->format('Y-m'),
            'teacher' => $course->teacher->only(['id', 'name']),
            'invoice_status' => $invoice?->status,
            'invoice_send_at' => $invoice?->created_at?->format('Y-m-d H:i:s'),
            'invoice_id' => $invoice?->id,
            'invoice_no' => $invoice?->no,
            'paid_at' => ''
        ];
    }

    /**
     * 格式化教师课程列表数据
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
     * 格式化学生课程列表数据
     *
     * @param LengthAwarePaginator $courses
     * @return LengthAwarePaginator
     */
    public function formatStudentCoursesList(LengthAwarePaginator $courses): LengthAwarePaginator
    {
        return $courses->tap(
            fn (LengthAwarePaginator $courses) => $courses->transform(
                function (Course $course) {
                    $invoice = $course->invoices->first();
                    return $course->only([
                        'id', 'name', 'fee', 'teacher_id'
                    ]) + [
                        'teacher' => $course->teacher->only(['id', 'name']),
                        'year_month' => $course->year_month->format('Y-m'),
                        'invoice_status' => $invoice?->status,
                        'invoice_send_at' => $invoice?->created_at->format('Y-m-d H:i:s'),
                        'invoice_id' => $invoice?->id,
                        'invoice_no' => $invoice?->no,
                        'paid_at' => '' //todo
                    ];
                }
            )
        );
    }
}

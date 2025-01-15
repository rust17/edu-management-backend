<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Http\Requests\Course\ListMyCourseRequest;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentController extends Controller
{
    /**
     * 获取学生的课程列表
     *
     * @param ListMyCourseRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function studentCourses(ListMyCourseRequest $request)
    {
        $query = $request->user()
            ->studentCourses()
            ->with(['teacher:id,name', 'invoices' =>
                fn ($query) => $query->where('student_id', $request->user()->id)
            ])
            ->latest('id');

        // 按年月筛选
        if ($request->filled('year_month')) {
            $query->where('year_month', Carbon::parse($request->year_month)->startOfMonth());
        }

        // 按关键词筛选
        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }

        $courses = $query->paginate(
            $request->input('per_page', 15)
        );

        return $this->success(
            '获取成功',
            $courses->tap(
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
                    })
            )
        );
    }

    /**
     * 学生查看课程详情
     *
     * @param Course $course
     * @return JsonResponse
     */
    public function studentCourse(Course $course)
    {
        // 检查学生是否已选该课程
        if (!$course->students()->where('student_id', auth()->id())->exists()) {
            return $this->error('您没有权限查看该课程', 1, 403);
        }

        // 加载教师信息和发票状态
        $course->load(['teacher:id,name']);
        $invoice = $course->invoices->first();

        return $this->success(
            '获取成功',
            $course->only([
                'id', 'name', 'fee', 'teacher_id'
            ]) + [
                'year_month' => $course->year_month->format('Y-m'),
                'teacher' => $course->teacher->only(['id', 'name']),
                'invoice_status' => $invoice?->status,
                'invoice_send_at' => $invoice?->created_at->format('Y-m-d H:i:s'),
                'invoice_id' => $invoice?->id,
                'invoice_no' => $invoice?->no,
                'paid_at' => '' //todo
            ]
        );
    }
}

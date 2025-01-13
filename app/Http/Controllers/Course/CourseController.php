<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Http\Requests\Course\AttachStudentsRequest;
use App\Models\Course;
use App\Http\Requests\Course\ListMyCourseRequest;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseController extends Controller
{
    /**
     * 创建课程
     *
     * @param CreateCourseRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateCourseRequest $request)
    {
        $course = Course::create([
            'name' => $request->name,
            'year_month' => Carbon::parse($request->year_month)->startOfMonth(),
            'fee' => $request->fee,
            'teacher_id' => $request->user()->id
        ]);

        return $this->success(
            '课程创建成功',
            $course->only(['id', 'name', 'fee', 'teacher_id']) + [
                'year_month' => $course->year_month->format('Y-m')
            ]
        );
    }

    /**
     * 关联学生
     *
     * @param AttachStudentsRequest $request
     * @param Course $course
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachStudents(AttachStudentsRequest $request, Course $course)
    {
        // 只关联新的学生
        if ($newStudentIds = collect($request->student_ids)
            ->diff($course->students->pluck('id'))
            ->toArray()
        ) {
            $course->students()->attach($newStudentIds);
        }

        return $this->success('课程学生设置成功');
    }

    /**
     * 查看我的课程
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
                'paid_at' => '' //todo
            ]
        );
    }
}

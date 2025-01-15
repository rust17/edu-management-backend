<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Http\Requests\Course\AttachStudentsRequest;
use App\Models\Course;
use App\Http\Requests\Course\ListMyCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CourseController extends Controller
{
    /**
     * 创建课程
     *
     * @param CreateCourseRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws Throwable
     */
    public function store(CreateCourseRequest $request)
    {
        try {
            DB::beginTransaction();

            $course = Course::create([
                'name' => $request->name,
                'year_month' => Carbon::parse($request->year_month)->startOfMonth(),
                'fee' => $request->fee,
                'teacher_id' => $request->user()->id
            ]);

            $course->students()->attach($request->student_ids);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $this->success(
            '课程创建成功',
            $course->only(['id', 'name', 'fee', 'teacher_id']) + [
                'year_month' => $course->year_month->format('Y-m'),
                'students' => $course->students->map(fn ($student) => $student->only(['id', 'name']))
            ]
        );
    }

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

    /**
     * 获取教师的课程列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function teacherCourses(Request $request)
    {
        $query = Course::with('students')
            ->where('teacher_id', auth()->id());

        // 按关键词筛选
        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }

        // 按年月筛选
        if ($request->filled('year_month')) {
            $query->where('year_month', Carbon::parse($request->year_month)->startOfMonth());
        }

        $courses = $query->latest('id')
            ->paginate(
                $request->input('per_page', 15)
            );

        return $this->success('获取成功', $courses->tap(
            fn (LengthAwarePaginator $courses) => $courses->transform(
                function (Course $course) {
                    return $course->only([
                        'id', 'name', 'fee', 'teacher_id'
                    ]) + [
                        'year_month' => $course->year_month->format('Y-m'),
                        'students' => $course->students->count()
                    ];
                })
        ));
    }

    /**
     * 教师查看课程详情
     *
     * @param Course $course
     * @return \Illuminate\Http\JsonResponse
     */
    public function teacherCourse(Course $course)
    {
        // 检查是否是课程的所有者
        if ($course->teacher_id !== auth()->id()) {
            return $this->error('您只能查看自己的课程', 1, 403);
        }

        // 加载关联的学生和他们的发票信息
        $course->load(['students' => fn ($query) => $query->select('users.id', 'users.name')
            ->with(['invoices' => fn ($query) => $query->where('course_id', $course->id)
                    ->select('invoices.id', 'invoices.student_id', 'invoices.status')
                ])
        ]);

        return $this->success('获取成功', $course->only([
            'id', 'name', 'fee', 'teacher_id'
        ]) + [
            'year_month' => $course->year_month->format('Y-m'),
            'students' => $course->students->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'invoice_status' => $student->invoices->first()?->status
            ])
        ]);
    }

    /**
     * 编辑课程
     *
     * @param UpdateCourseRequest $request
     * @param Course $course
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        // 检查是否是课程的所有者
        if ($course->teacher_id !== $request->user()->id) {
            return $this->error('您只能编辑自己的课程', 1, 403);
        }

        try {
            DB::beginTransaction();

            // 更新课程基本信息
            $course->update([
                'name' => $request->name,
                'year_month' => Carbon::parse($request->year_month)->startOfMonth(),
                'fee' => $request->fee
            ]);

            // 如果提供了学生列表，则更新关联的学生
            if ($request->has('student_ids')) {
                $course->students()->sync($request->student_ids);
            }

            DB::commit();

            // 重新加载关联关系
            $course->load('students');

            return $this->success('课程更新成功', $course->only([
                'id', 'name', 'fee', 'teacher_id'
            ]) + [
                'year_month' => $course->year_month->format('Y-m'),
                'students' => $course->students->map(fn ($student) => $student->only(['id', 'name']))
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

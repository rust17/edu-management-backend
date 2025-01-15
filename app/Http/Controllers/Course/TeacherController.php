<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Models\Course;
use App\Http\Requests\Course\UpdateCourseRequest;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class TeacherController extends Controller
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

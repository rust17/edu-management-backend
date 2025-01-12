<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Http\Requests\Course\AttachStudentsRequest;
use App\Models\Course;
use App\Http\Requests\Course\ListMyCourseRequest;
use Carbon\Carbon;

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
            'year_month' => $request->year_month,
            'fee' => $request->fee,
            'teacher_id' => $request->user()->id
        ]);

        return $this->success(
            '课程创建成功',
            $course->load(['teacher', 'students'])
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
        $course->students()->attach($request->student_ids);

        return $this->success(
            '课程学生设置成功',
            $course->load(['teacher', 'students'])
        );
    }

    /**
     * 查看我的课程
     *
     * @param ListMyCourseRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function my(ListMyCourseRequest $request)
    {
        $query = $request->user()
            ->studentCourses()
            ->with(['teacher'])
            ->latest('id');

        // 按年月筛选
        if ($request->filled('year_month')) {
            $query->where('year_month', Carbon::parse($request->year_month)->format('Y-m'));
        }

        $courses = $query->paginate(
            $request->input('per_page', 15)
        );

        return $this->success('获取成功', $courses);
    }
}

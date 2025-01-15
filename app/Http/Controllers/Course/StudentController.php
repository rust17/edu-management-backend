<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Http\Requests\Course\ListMyCourseRequest;
use App\Http\Services\CourseService;
use App\Http\Traits\FormatCourseTrait;

class StudentController extends Controller
{
    use FormatCourseTrait;

    protected CourseService $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * 获取学生的课程列表
     */
    public function studentCourses(ListMyCourseRequest $request)
    {
        $courses = $this->courseService
            ->getStudentCoursesQuery($request->user()->id, $request->all())
            ->paginate($request->input('per_page', 15));

        return $this->success('获取成功', $this->formatStudentCoursesList($courses));
    }

    /**
     * 学生查看课程详情
     */
    public function studentCourse(Course $course)
    {
        if (!$course->students()->where('student_id', auth()->id())->exists()) {
            return $this->error('您没有权限查看该课程', 1, 403);
        }

        $course->load(['teacher:id,name']);

        return $this->success('获取成功', $this->formatStudentCourseDetail($course));
    }
}

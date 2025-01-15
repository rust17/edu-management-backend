<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Models\Course;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Services\CourseService;
use Illuminate\Http\Request;
use App\Http\Traits\FormatCourseTrait;

class TeacherController extends Controller
{
    use FormatCourseTrait;

    protected CourseService $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * 创建课程
     */
    public function store(CreateCourseRequest $request)
    {
        $course = $this->courseService->create($request->validated(), $request->user()->id);

        return $this->success('课程创建成功', $this->formatTeacherCourseDetail($course));
    }

    /**
     * 获取教师的课程列表
     */
    public function teacherCourses(Request $request)
    {
        $courses = $this->courseService
            ->getTeacherCoursesQuery($request->user()->id, $request->all())
            ->paginate($request->input('per_page', 15));

        return $this->success('获取成功', $this->formatTeacherCoursesList($courses));
    }

    /**
     * 教师查看课程详情
     */
    public function teacherCourse(Course $course)
    {
        if ($course->teacher_id !== auth()->id()) {
            return $this->error('您只能查看自己的课程', 1, 403);
        }

        return $this->success('获取成功', $this->formatTeacherCourseDetail($course));
    }

    /**
     * 编辑课程
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        if ($course->teacher_id !== $request->user()->id) {
            return $this->error('您只能编辑自己的课程', 1, 403);
        }

        $course = $this->courseService->update($course, $request->validated());

        return $this->success('课程更新成功', $this->formatTeacherCourseDetail($course));
    }
}

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
     * Create a course
     */
    public function store(CreateCourseRequest $request)
    {
        $course = $this->courseService->create($request->validated(), $request->user()->id);

        return $this->success('Course created successfully', $this->formatTeacherCourseDetail($course));
    }

    /**
     * Get the teacher's course list
     */
    public function teacherCourses(Request $request)
    {
        $courses = $this->courseService
            ->getTeacherCoursesQuery($request->user()->id, $request->all())
            ->paginate($request->input('per_page', 15));

        return $this->success('Get successfully', $this->formatTeacherCoursesList($courses));
    }

    /**
     * Teacher view course details
     */
    public function teacherCourse(Course $course)
    {
        if ($course->teacher_id !== auth()->id()) {
            return $this->error('You can only view your own courses', 1, 403);
        }

        return $this->success('Get successfully', $this->formatTeacherCourseDetail($course));
    }

    /**
     * Edit course
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        if ($course->teacher_id !== $request->user()->id) {
            return $this->error('You can only edit your own courses', 1, 403);
        }

        $course = $this->courseService->update($course, $request->validated());

        return $this->success('Course updated successfully', $this->formatTeacherCourseDetail($course));
    }
}

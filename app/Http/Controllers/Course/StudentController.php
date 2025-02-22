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
     * Get student's course list
     */
    public function studentCourses(ListMyCourseRequest $request)
    {
        $courses = $this->courseService
            ->getStudentCoursesQuery($request->user()->id, $request->all())
            ->paginate($request->input('per_page', 15));

        return $this->success('Get successfully', $this->formatStudentCoursesList($courses));
    }

    /**
     * Student view course details
     */
    public function studentCourse(Course $course)
    {
        if (!$course->students()->where('student_id', auth()->id())->exists()) {
            return $this->error('You do not have permission to view this course', 1, 403);
        }

        $course->load(['teacher:id,name']);

        return $this->success('Get successfully', $this->formatStudentCourseDetail($course));
    }
}

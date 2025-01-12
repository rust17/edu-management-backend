<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CreateCourseRequest;
use App\Http\Requests\Course\AttachStudentsRequest;
use App\Models\Course;

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

        return response()->json([
            'message' => '课程创建成功',
            'data' => $course->load(['teacher', 'students'])
        ]);
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

        return response()->json([
            'message' => '课程学生设置成功',
            'data' => $course->load(['teacher', 'students'])
        ]);
    }
}

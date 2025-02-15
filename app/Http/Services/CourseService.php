<?php

namespace App\Http\Services;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class CourseService
{
    /**
     * Create course
     *
     * @param array $data
     * @param int $teacherId
     * @return Course
     * @throws Throwable
     */
    public function create(array $data, int $teacherId): Course
    {
        try {
            DB::beginTransaction();

            $course = Course::create([
                'name' => $data['name'],
                'year_month' => Carbon::parse($data['year_month'])->startOfMonth(),
                'fee' => $data['fee'],
                'teacher_id' => $teacherId
            ]);

            $course->students()->attach($data['student_ids']);

            DB::commit();
            return $course;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update course
     *
     * @param Course $course
     * @param array $data
     * @return Course
     * @throws Throwable
     */
    public function update(Course $course, array $data): Course
    {
        try {
            DB::beginTransaction();

            $course->update([
                'name' => $data['name'],
                'year_month' => Carbon::parse($data['year_month'])->startOfMonth(),
                'fee' => $data['fee']
            ]);

            if (isset($data['student_ids'])) {
                $course->students()->sync($data['student_ids']);
            }

            DB::commit();

            $course->load('students');
            return $course;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get the query builder for the teacher course list
     *
     * @param int $teacherId
     * @param array $filters
     * @return Builder
     */
    public function getTeacherCoursesQuery(int $teacherId, array $filters): Builder
    {
        $query = Course::with('students')
            ->where('teacher_id', $teacherId);

        if (!empty($filters['keyword'])) {
            $query->where('name', 'like', '%' . $filters['keyword'] . '%');
        }

        if (!empty($filters['year_month'])) {
            $query->where('year_month', Carbon::parse($filters['year_month'])->startOfMonth());
        }

        return $query->latest('id');
    }

    /**
     * Get the query builder for the student course list
     *
     * @param int $studentId
     * @param array $filters
     * @return Builder
     */
    public function getStudentCoursesQuery(int $studentId, array $filters): Builder
    {
        $query = Course::whereHas('students', fn ($query) => $query->where('users.id', $studentId))
            ->with([
                'teacher:id,name',
                'invoices' => fn ($query) => $query->where('student_id', $studentId)
            ])->latest('id');

        if (!empty($filters['keyword'])) {
            $query->where('name', 'like', '%' . $filters['keyword'] . '%');
        }

        if (!empty($filters['year_month'])) {
            $query->where('year_month', Carbon::parse($filters['year_month'])->startOfMonth());
        }

        return $query;
    }
}

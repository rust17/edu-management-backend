<?php

use App\Models\CourseStudent;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseStudentFactory extends Factory
{
    protected $model = CourseStudent::class;

    public function definition(): array
    {
        return [
            'course_id' => fn () => factory(Course::class)->create()->id,
            'student_id' => fn () => factory(User::class)->create(['role' => 'student'])->id,
        ];
    }
}

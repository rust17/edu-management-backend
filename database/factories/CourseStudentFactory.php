<?php

namespace Database\Factories;

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
            'course_id' => fn () => Course::factory()->create()->id,
            'student_id' => fn () => User::factory()->create(['role' => User::ROLE_STUDENT])->id,
        ];
    }
}

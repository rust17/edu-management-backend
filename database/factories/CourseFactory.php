<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $subjects = [
            'Advanced Mathematics', 'College English', 'Computer Fundamentals',
            'Data Structures', 'Operating Systems', 'Database Principles',
            'Software Engineering', 'Computer Networks', 'Artificial Intelligence',
            'Machine Learning', 'Web Development', 'Mobile App Development',
            'Python Programming', 'Java Programming', 'C++ Programming',
            'Network Security', 'Cloud Computing', 'Big Data Analysis',
            'Internet of Things Technology', 'Blockchain Basics'
        ];
        $levels = ['Beginner', 'Intermediate', 'Advanced'];
        $types = ['Theory', 'Practice', 'Seminar'];

        // Generate a random year and month (the first day of each month) within the last two years
        $date = Carbon::now()->subMonths(rand(0, 24))->startOfMonth();

        return [
            'name' => fake()->randomElement($subjects) . ' ' .
                fake()->randomElement($levels) . ' ' .
                fake()->randomElement($types) . ' Course',
            'year_month' => $date,
            'fee' => fake()->randomFloat(2, 100, 10000),
            'teacher_id' => User::factory()->create(['role' => User::ROLE_TEACHER])->id
        ];
    }
}


<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $course = Course::factory()->create();

        return [
            'course_id' => $course->id,
            'student_id' => fn () => User::factory()->create(['role' => User::ROLE_STUDENT])->id,
            'status' => fake()->randomElement(['pending', 'paid', 'failed']),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'no' => Invoice::generateNo(),
        ];
    }
}

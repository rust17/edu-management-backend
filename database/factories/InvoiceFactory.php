<?php

use App\Models\Invoice;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $course = factory(Course::class)->create();

        return [
            'course_id' => $course->id,
            'student_id' => fn () => factory(User::class)->create(['role' => 'student'])->id,
            'status' => fake()->randomElement(['pending', 'paid', 'failed']),
            'amount' => fake()->randomFloat(2, 100, 10000),
        ];
    }
}

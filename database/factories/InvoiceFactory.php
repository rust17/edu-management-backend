<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Invoice;
use Faker\Generator as Faker;

$factory->define(Invoice::class, function (Faker $faker) {
    $course = factory(Course::class)->create();
    return [
        'course_id' => $course->id,
        'student_id' => function () {
            return factory(User::class)->create(['role' => 'student'])->id;
        },
        'status' => $faker->randomElement(['pending', 'paid', 'failed']),
        'amount' => $faker->randomFloat(2, 100, 10000),
    ];
});

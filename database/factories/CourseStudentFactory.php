<?php

use App\Models\User;
use App\Models\Course;
use App\Models\CourseStudent;
use Faker\Generator as Faker;

$factory->define(CourseStudent::class, function (Faker $faker) {
    return [
        'course_id' => function () {
            return factory(Course::class)->create()->id;
        },
        'student_id' => function () {
            return factory(User::class)->create(['role' => 'student'])->id;
        },
    ];
});

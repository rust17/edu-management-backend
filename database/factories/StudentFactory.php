<?php

use App\Models\User;
use App\Models\Student;
use Faker\Generator as Faker;

$factory->define(Student::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(User::class)->state('student')->create()->id;
        },
    ];
});
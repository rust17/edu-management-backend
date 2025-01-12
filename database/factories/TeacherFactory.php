<?php

use App\Models\User;
use App\Models\Teacher;
use Faker\Generator as Faker;

$factory->define(Teacher::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(User::class)->state('teacher')->create()->id;
        },
    ];
});

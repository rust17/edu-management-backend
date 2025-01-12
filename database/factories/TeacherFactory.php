<?php

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'user_id' => fn () => factory(User::class)->state('teacher')->create()->id,
        ];
    }
}

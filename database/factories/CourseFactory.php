<?php

use Faker\Generator as Faker;
use App\Models\User;
use App\Models\Course;
use Carbon\Carbon;

$factory->define(Course::class, function (Faker $faker) {
    $subjects = [
        '高等数学', '大学英语', '计算机基础',
        '数据结构', '操作系统', '数据库原理',
        '软件工程', '计算机网络', '人工智能',
        '机器学习', 'Web开发', '移动应用开发',
        'Python编程', 'Java编程', 'C++编程',
        '网络安全', '云计算', '大数据分析',
        '物联网技术', '区块链基础'
    ];

    $levels = ['初级', '中级', '高级'];
    $types = ['理论', '实践', '研讨'];

    // 生成最近两年内的随机年月（每月1号）
    $date = Carbon::now()->subMonths(rand(0, 24))->startOfMonth();

    return [
        'name' => $faker->randomElement($subjects) .
                 $faker->randomElement($levels) .
                 $faker->randomElement($types) . '课程',
        'year_month' => $date,
        'fee' => $faker->randomFloat(2, 100, 10000),
        'teacher_id' => function () {
            return factory(User::class)->create(['role' => 'teacher'])->id;
        },
    ];
});

<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:install');
    }

    /**
     * 测试学生可以查看自己的课程
     */
    public function test_student_can_view_own_courses()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        // 创建一些课程并关联到学生
        $courses = Course::factory()->count(3)->create();
        $courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // 创建一些不属于该学生的课程
        Course::factory()->count(2)->create();

        $response = $this->actingAs($student, 'api')
            ->getJson('/api/courses/student-courses');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => '获取成功'
            ])
            ->assertJsonCount(3, 'data.data')
            ->assertJsonStructure([
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'year_month',
                            'fee',
                            'teacher_id',
                            'teacher' => [
                                'id',
                                'name',
                            ],
                            'invoice_status',
                            'invoice_send_at',
                            'paid_at'
                        ]
                    ],
                    'total',
                    'per_page'
                ]
            ]);
    }

    /**
     * 测试教师不能使用查看我的课程接口
     */
    public function test_teacher_cannot_view_courses_as_student()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/courses/student-courses');

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => '您没有权限执行此操作',
                'data' => null
            ]);
    }

    /**
     * 测试按年月筛选课程
     */
    public function test_student_can_filter_courses_by_year_month()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        // 创建 2024-03 的课程
        $march2024Courses = Course::factory()->count(2)->create([
            'year_month' => '2024-03'
        ]);
        $march2024Courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // 创建 2024-04 的课程
        $april2024Courses = Course::factory()->count(3)->create([
            'year_month' => '2024-04'
        ]);
        $april2024Courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        $response = $this->actingAs($student, 'api')
            ->getJson('/api/courses/student-courses?year_month=2024-03');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => '获取成功'
            ])
            ->assertJsonCount(2, 'data.data');
    }

    /**
     * 测试按关键词筛选课程
     */
    public function test_student_can_filter_courses_by_keyword()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        // 创建包含特定关键词的课程
        Course::factory()->count(2)->create([
            'name' => '高等数学'
        ])->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // 创建其他课程
        Course::factory()->count(3)->create([
            'name' => '英语课程'
        ])->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        $response = $this->actingAs($student, 'api')
            ->getJson('/api/courses/student-courses?keyword=数学');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => '获取成功'
            ])
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.name', '高等数学')
            ->assertJsonPath('data.data.1.name', '高等数学');
    }

    /**
     * 测试学生可以查看自己已选课程的详情
     */
    public function test_student_can_view_own_course_detail()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        $course = Course::factory()->create();
        $course->students()->attach($student->id);

        $response = $this->actingAs($student, 'api')
            ->getJson("/api/courses/student-courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => '获取成功'
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'year_month',
                    'fee',
                    'teacher_id',
                    'teacher' => [
                        'id',
                        'name',
                    ],
                    'invoice_status',
                    'invoice_send_at',
                    'paid_at'
                ]
            ]);
    }

    /**
     * 测试学生不能查看未选课程的详情
     */
    public function test_student_cannot_view_unselected_course_detail()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        $course = Course::factory()->create();
        // 不关联学生和课程

        $response = $this->actingAs($student, 'api')
            ->getJson("/api/courses/student-courses/{$course->id}");

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => '您没有权限查看该课程',
                'data' => null
            ]);
    }
}

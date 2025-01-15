<?php

namespace Tests\Feature;

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
     * 测试教师可以获取学生列表
     */
    public function test_teacher_can_get_student_list()
    {
        // 创建一个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);

        // 创建一些学生
        $students = User::factory()->count(3)->create(['role' => 'student']);

        // 请求学生列表
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/students');

        // 验证响应
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                    ]
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => '获取成功',
                'data' => $students->map(fn ($student) => $student->only(['id', 'name']))->toArray()
            ]);
    }

    /**
     * 测试按关键词搜索学生
     */
    public function test_teacher_can_search_students_by_keyword()
    {
        // 创建一个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);

        // 创建一些带特定名字的学生
        User::factory()->create([
            'role' => 'student',
            'name' => '张三',
            'email' => 'zhangsan@example.com'
        ]);
        User::factory()->create([
            'role' => 'student',
            'name' => '李四',
            'email' => 'lisi@example.com'
        ]);

        // 测试按名字搜索
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/students?keyword=张三');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '张三');

        // 测试按邮箱搜索
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/students?keyword=lisi@example.com');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '李四');
    }
}

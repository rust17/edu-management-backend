<?php

namespace Tests\Feature\Statistics;

use App\Models\Course;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:install');
    }

    /**
     * 测试教师可以获取统计信息
     */
    public function test_teacher_can_get_statistics()
    {
        // 创建一个教师用户
        $teacher = User::factory()->create(['role' => 'teacher']);

        // 创建3个课程
        $courses = Course::factory()->count(3)->create([
            'teacher_id' => $teacher->id
        ]);

        // 为每个课程创建2个账单
        $courses->each(function ($course) {
            $students = User::factory()->count(2)->create(['role' => 'student']);
            $course->students()->attach($students->pluck('id'));

            $students->each(function ($student) use ($course) {
                Invoice::factory()->create([
                    'course_id' => $course->id,
                    'student_id' => $student->id
                ]);
            });
        });

        // 创建其他教师的课程和账单
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $otherCourse = Course::factory()->create(['teacher_id' => $otherTeacher->id]);
        Invoice::factory()->create([
            'course_id' => $otherCourse->id,
            'student_id' => User::factory()->create(['role' => 'student'])->id
        ]);

        // 请求统计信息
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/teacher-statistics');

        // 验证响应
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'course_count',
                    'invoice_count'
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'course_count' => 3,
                    'invoice_count' => 6
                ]
            ]);
    }

    /**
     * 测试学生可以获取统计信息
     */
    public function test_student_can_get_statistics()
    {
        // 创建一个学生用户
        $student = User::factory()->create(['role' => 'student']);

        // 创建3个课程并关联到学生
        $courses = Course::factory()->count(3)->create([
            'teacher_id' => User::factory()->create(['role' => 'teacher'])->id
        ]);
        $courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // 创建2个待支付账单和1个已支付账单
        $courses->take(2)->each(function ($course) use ($student) {
            Invoice::factory()->create([
                'course_id' => $course->id,
                'student_id' => $student->id,
                'status' => Invoice::STATUS_PENDING
            ]);
        });
        Invoice::factory()->create([
            'course_id' => $courses->last()->id,
            'student_id' => $student->id,
            'status' => Invoice::STATUS_PAID
        ]);

        // 创建其他学生的课程和账单
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherCourse = Course::factory()->create();
        $otherCourse->students()->attach($otherStudent->id);
        Invoice::factory()->create([
            'course_id' => $otherCourse->id,
            'student_id' => $otherStudent->id
        ]);

        // 请求统计信息
        $response = $this->actingAs($student, 'api')
            ->getJson('/api/student-statistics');

        // 验证响应
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'course_count',
                    'pending_invoice_count'
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => '获取成功',
                'data' => [
                    'course_count' => 3,
                    'pending_invoice_count' => 2
                ]
            ]);
    }
}

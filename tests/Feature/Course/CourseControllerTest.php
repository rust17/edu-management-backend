<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\User;
use App\Models\CourseStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:install');
    }

    /**
     * 测试教师可以创建课程
     */
    public function test_teacher_can_create_course()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->postJson('/api/courses', [
                'name' => '数学课',
                'year_month' => '2024-03',
                'fee' => 100
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'year_month',
                    'fee',
                    'teacher_id',
                    'teacher',
                    'students'
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => '课程创建成功',
                'data' => [
                    'name' => '数学课',
                    'year_month' => '2024-03',
                    'fee' => 100,
                    'teacher_id' => $teacher->id
                ]
            ]);
    }

    /**
     * 测试学生不能创建课程
     */
    public function test_student_cannot_create_course()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        $response = $this->actingAs($student, 'api')
            ->postJson('/api/courses', [
                'name' => '数学课',
                'year_month' => '2024-03',
                'fee' => 100
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => '只有教师才能创建课程',
                'data' => null
            ]);
    }

    /**
     * 测试课程创建验证
     */
    public function test_course_creation_validation()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->postJson('/api/courses', []);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 1,
                'message' => '验证失败'
            ])
            ->assertJsonValidationErrors(['name', 'year_month', 'fee'], 'data');
    }

    /**
     * 测试教师可以关联学生到自己的课程
     */
    public function test_teacher_can_attach_students_to_own_course()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $course = Course::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $students = User::factory()->count(3)->create([
            'role' => User::ROLE_STUDENT
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->postJson("/api/courses/{$course->id}/attach-students", [
                'student_ids' => $students->pluck('id')->toArray()
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => '课程学生设置成功'
            ]);

        $course->fresh();
        $this->assertEquals(
            $students->only('id', 'name', 'email')->toArray(),
            $course->students->only('id', 'name', 'email')->toArray()
        );
        $students->each(function ($student) use ($course) {
            $this->assertDatabaseHas(CourseStudent::class, [
                'course_id' => $course->id,
                'student_id' => $student->id
            ]);
        });
    }

    /**
     * 测试非教师用户不能关联学生到课程
     */
    public function test_non_teacher_cannot_attach_students()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        $course = Course::factory()->create();
        $students = User::factory()->count(3)->create([
            'role' => User::ROLE_STUDENT
        ]);

        $response = $this->actingAs($student, 'api')
            ->postJson("/api/courses/{$course->id}/attach-students", [
                'student_ids' => $students->pluck('id')->toArray()
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => '只有教师才能关联学生到课程',
                'data' => null
            ]);
    }

    /**
     * 测试教师不能关联学生到其他教师的课程
     */
    public function test_teacher_cannot_attach_students_to_other_teachers_course()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $otherTeacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $course = Course::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $students = User::factory()->count(3)->create([
            'role' => User::ROLE_STUDENT
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->postJson("/api/courses/{$course->id}/attach-students", [
                'student_ids' => $students->pluck('id')->toArray()
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => '您只能关联学生到自己的课程',
                'data' => null
            ]);
    }

    /**
     * 测试关联非学生用户到课程时会失败
     */
    public function test_cannot_attach_non_student_users_to_course()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $course = Course::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        $nonStudents = User::factory()->count(2)->create([
            'role' => User::ROLE_TEACHER
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->postJson("/api/courses/{$course->id}/attach-students", [
                'student_ids' => $nonStudents->pluck('id')->toArray()
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 1,
                'message' => '验证失败'
            ])
            ->assertJsonValidationErrors(['student_ids.0', 'student_ids.1'], 'data');
    }
}

<?php

namespace Tests\Feature\Invoice;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Invoice;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeacherControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建测试数据
        $this->teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->course = Course::factory()->create([
            'teacher_id' => $this->teacher->id
        ]);
    }

    /**
     * 测试教师创建账单
     */
    public function test_teacher_can_create_invoice(): void
    {
        Passport::actingAs($this->teacher);

        $response = $this->postJson('/api/invoices', [
            'course_id' => $this->course->id,
            'student_ids' => [$this->student->id]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', '账单创建成功');

        $this->assertDatabaseHas('invoices', [
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'amount' => $this->course->fee,
            'status' => Invoice::STATUS_PENDING
        ]);
    }

    /**
     * 测试教师不能创建其他教师课程的账单
     */
    public function test_teacher_cannot_create_other_teachers_invoice(): void
    {
        Passport::actingAs($this->teacher);

        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherCourse = Course::factory()->create(['teacher_id' => $otherTeacher->id]);

        $response = $this->postJson('/api/invoices', [
            'course_id' => $otherCourse->id,
            'student_ids' => [$this->student->id],
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', '您只能创建自己课程的账单');
    }

    /**
     * 测试教师发送账单
     */
    public function test_teacher_can_send_invoice(): void
    {
        Passport::actingAs($this->teacher);

        $invoice = Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'status' => Invoice::STATUS_PENDING
        ]);

        $response = $this->postJson("/api/invoices/{$this->course->id}/send", [
            'student_ids' => [$this->student->id]
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', '账单已发送');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => Invoice::STATUS_PENDING
        ]);
    }

    /**
     * 测试教师不能发送其他教师课程的账单
     */
    public function test_teacher_cannot_send_other_teachers_invoice(): void
    {
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $otherCourse = Course::factory()->create(['teacher_id' => $otherTeacher->id]);

        Passport::actingAs($this->teacher);

        $response = $this->postJson("/api/invoices/{$otherCourse->id}/send", [
            'student_ids' => [$this->student->id]
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', '您只能发送自己课程的账单');
    }

    /**
     * 测试教师可以获取自己课程的账单列表
     */
    public function test_teacher_can_get_own_invoices(): void
    {
        Passport::actingAs($this->teacher);

        // 创建3个账单
        Course::factory()->count(3)
            ->create(['teacher_id' => $this->teacher->id])
            ->map(function ($course) {
                $course->students()->attach($this->student->id);
                Invoice::factory()->create([
                    'course_id' => $course->id,
                    'student_id' => $this->student->id
                ]);
            });


        // 创建其他教师的账单
        $otherTeacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        Invoice::factory()->create([
            'course_id' => Course::factory()->create(['teacher_id' => $otherTeacher->id]),
            'student_id' => $this->student->id
        ]);

        $response = $this->getJson('/api/invoices/teacher-invoices');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data.data')  // 分页数据在 data.data 中
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'course_id',
                            'student_id',
                            'amount',
                            'status',
                            'send_at',
                            'paid_at',
                            'course' => [
                                'id',
                                'name',
                                'year_month'
                            ],
                            'student_name',
                        ]
                    ]
                ]
            ]);
    }

    /**
     * 测试教师按状态筛选账单
     */
    public function test_teacher_can_filter_invoices_by_status(): void
    {
        Passport::actingAs($this->teacher);

        // 创建不同状态的账单
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'status' => Invoice::STATUS_PENDING
        ]);

        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'status' => Invoice::STATUS_PAID
        ]);

        $response = $this->getJson('/api/invoices/teacher-invoices?status=pending');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.status', Invoice::STATUS_PENDING);
    }

    /**
     * 测试教师按课程关键词筛选账单
     */
    public function test_teacher_can_filter_invoices_by_course_keyword(): void
    {
        Passport::actingAs($this->teacher);

        // 创建数学课程的账单
        $mathCourse = Course::factory()->create([
            'name' => '高等数学',
            'teacher_id' => $this->teacher->id
        ]);
        Invoice::factory()->count(2)->create([
            'course_id' => $mathCourse->id,
            'student_id' => $this->student->id
        ]);

        // 创建英语课程的账单
        $englishCourse = Course::factory()->create([
            'name' => '大学英语',
            'teacher_id' => $this->teacher->id
        ]);
        Invoice::factory()->create([
            'course_id' => $englishCourse->id,
            'student_id' => $this->student->id
        ]);

        $response = $this->getJson('/api/invoices/teacher-invoices?keyword=数学');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.course.name', '高等数学')
            ->assertJsonPath('data.data.1.course.name', '高等数学');
    }

    /**
     * 测试教师按课程年月筛选账单
     */
    public function test_teacher_can_filter_invoices_by_course_year_month(): void
    {
        Passport::actingAs($this->teacher);

        // 创建2024-03的课程账单
        $marchCourse = Course::factory()->create([
            'year_month' => '2024-03',
            'teacher_id' => $this->teacher->id
        ]);
        Invoice::factory()->count(2)->create([
            'course_id' => $marchCourse->id,
            'student_id' => $this->student->id
        ]);

        // 创建2024-04的课程账单
        $aprilCourse = Course::factory()->create([
            'year_month' => '2024-04',
            'teacher_id' => $this->teacher->id
        ]);
        Invoice::factory()->create([
            'course_id' => $aprilCourse->id,
            'student_id' => $this->student->id
        ]);

        $response = $this->getJson('/api/invoices/teacher-invoices?year_month=2024-03');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.course.year_month', '2024-03')
            ->assertJsonPath('data.data.1.course.year_month', '2024-03');
    }

    /**
     * 测试教师按账单发送时间范围筛选账单
     */
    public function test_teacher_can_filter_invoices_by_send_time_range(): void
    {
        Passport::actingAs($this->teacher);

        // 创建一个较早的账单
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'created_at' => '2024-03-01 10:00:00'
        ]);

        // 创建一个较晚的账单
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'created_at' => '2024-03-15 10:00:00'
        ]);

        // 创建一个更晚的账单
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'created_at' => '2024-03-31 10:00:00'
        ]);

        $response = $this->getJson('/api/invoices/teacher-invoices?send_start=2024-03-10&send_end=2024-03-20');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.send_at', '2024-03-15 10:00:00');
    }
}

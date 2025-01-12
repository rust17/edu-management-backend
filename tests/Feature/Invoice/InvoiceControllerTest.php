<?php

namespace Tests\Feature\Invoice;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Invoice;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceControllerTest extends TestCase
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
            'student_id' => $this->student->id
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', '账单创建成功')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'course_id',
                    'student_id',
                    'amount',
                    'status',
                    'course' => ['id', 'name'],
                    'student' => ['id', 'name']
                ]
            ]);

        $this->assertDatabaseHas('invoices', [
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'amount' => $this->course->fee,
            'status' => Invoice::STATUS_PENDING
        ]);
    }

    /**
     * 测试学生不能创建账单
     */
    public function test_student_cannot_create_invoice(): void
    {
        Passport::actingAs($this->student);

        $response = $this->postJson('/api/invoices', [
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', '只有教师才能创建账单');
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
            'student_id' => $this->student->id,
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

        $response = $this->postJson("/api/invoices/{$invoice->id}/send");

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

        $invoice = Invoice::factory()->create([
            'course_id' => $otherCourse->id,
            'student_id' => $this->student->id,
            'status' => Invoice::STATUS_PENDING
        ]);

        $response = $this->postJson("/api/invoices/{$invoice->id}/send");

        $response->assertStatus(403)
            ->assertJsonPath('message', '您只能发送自己课程的账单');
    }

    /**
     * 测试学生查看自己的账单
     */
    public function test_student_can_view_own_invoices(): void
    {
        Passport::actingAs($this->student);

        // 创建3个账单
        Invoice::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id
        ]);

        // 创建一个其他学生的账单
        $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $otherStudent->id
        ]);

        $response = $this->getJson('/api/invoices/my');

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
                            'course' => [
                                'id',
                                'name',
                                'teacher' => [
                                    'id',
                                    'name'
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
    }

    /**
     * 测试学生按状态筛选账单
     */
    public function test_student_can_filter_invoices_by_status(): void
    {
        Passport::actingAs($this->student);

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

        $response = $this->getJson('/api/invoices/my?status=pending');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.status', Invoice::STATUS_PENDING);
    }

    /**
     * 测试教师不能查看学生的账单列表
     */
    public function test_teacher_cannot_view_student_invoices(): void
    {
        Passport::actingAs($this->teacher);

        $response = $this->getJson('/api/invoices/my');

        $response->assertStatus(403)
            ->assertJsonPath('message', '只有学生才能查看我的账单');
    }
}

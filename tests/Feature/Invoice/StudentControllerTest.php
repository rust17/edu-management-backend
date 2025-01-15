<?php

namespace Tests\Feature\Invoice;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Invoice;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentControllerTest extends TestCase
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
     * 测试学生查看自己的账单
     */
    public function test_student_can_view_own_invoices(): void
    {
        Passport::actingAs($this->student);

        // 创建3个账单
        Invoice::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'sent_at' => now()
        ]);

        // 创建一个其他学生的账单
        $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $otherStudent->id,
            'sent_at' => now()
        ]);

        $response = $this->getJson('/api/invoices/student-invoices');

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
            'status' => Invoice::STATUS_PENDING,
            'sent_at' => now()
        ]);

        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'status' => Invoice::STATUS_PAID,
            'sent_at' => now()
        ]);

        $response = $this->getJson('/api/invoices/student-invoices?status=pending');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.status', Invoice::STATUS_PENDING);
    }

    /**
     * 测试学生按课程关键词筛选账单
     */
    public function test_student_can_filter_invoices_by_course_keyword(): void
    {
        Passport::actingAs($this->student);

        // 创建数学课程的账单
        $mathCourse = Course::factory()->create([
            'name' => '高等数学',
            'teacher_id' => $this->teacher->id
        ]);
        Invoice::factory()->count(2)->create([
            'course_id' => $mathCourse->id,
            'student_id' => $this->student->id,
            'sent_at' => now()
        ]);

        // 创建英语课程的账单
        $englishCourse = Course::factory()->create([
            'name' => '大学英语',
            'teacher_id' => $this->teacher->id
        ]);
        Invoice::factory()->create([
            'course_id' => $englishCourse->id,
            'student_id' => $this->student->id,
            'sent_at' => now()
        ]);

        $response = $this->getJson('/api/invoices/student-invoices?keyword=数学');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.course.name', '高等数学')
            ->assertJsonPath('data.data.1.course.name', '高等数学');
    }

    /**
     * 测试学生按课程年月筛选账单
     */
    public function test_student_can_filter_invoices_by_course_year_month(): void
    {
        Passport::actingAs($this->student);

        // 创建2024-03的课程账单
        $marchCourse = Course::factory()->create([
            'year_month' => '2024-03',
            'teacher_id' => $this->teacher->id
        ]);
        Invoice::factory()->count(2)->create([
            'course_id' => $marchCourse->id,
            'student_id' => $this->student->id,
            'sent_at' => now()
        ]);

        // 创建2024-04的课程账单
        $aprilCourse = Course::factory()->create([
            'year_month' => '2024-04',
            'teacher_id' => $this->teacher->id
        ]);
        Invoice::factory()->create([
            'course_id' => $aprilCourse->id,
            'student_id' => $this->student->id,
            'sent_at' => now()
        ]);

        $response = $this->getJson('/api/invoices/student-invoices?year_month=2024-03');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.course.year_month', '2024-03')
            ->assertJsonPath('data.data.1.course.year_month', '2024-03');
    }

    /**
     * 测试学生按账单发送时间范围筛选账单
     */
    public function test_student_can_filter_invoices_by_send_time_range(): void
    {
        Passport::actingAs($this->student);

        // 创建一个较早的账单
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'sent_at' => '2024-03-01 10:00:00'
        ]);

        // 创建一个较晚的账单
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'sent_at' => '2024-03-15 10:00:00'
        ]);

        // 创建一个更晚的账单
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'sent_at' => '2024-03-31 10:00:00'
        ]);

        $response = $this->getJson('/api/invoices/student-invoices?send_start=2024-03-10&send_end=2024-03-20');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.send_at', '2024-03-15 10:00:00');
    }

    /**
     * 测试学生可以查看自己的账单详情
     */
    public function test_student_can_view_own_invoice_detail(): void
    {
        Passport::actingAs($this->student);

        $invoice = Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'status' => Invoice::STATUS_PENDING,
            'sent_at' => now()
        ]);

        $response = $this->getJson("/api/invoices/student-invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonPath('message', '获取成功')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'no',
                    'course_id',
                    'student_id',
                    'amount',
                    'status',
                    'send_at',
                    'paid_at',
                    'course' => [
                        'id',
                        'name',
                        'year_month',
                        'teacher_name',
                    ]
                ]
            ]);
    }

    /**
     * 测试学生不能查看其他学生的账单详情
     */
    public function test_student_cannot_view_other_students_invoice_detail(): void
    {
        Passport::actingAs($this->student);

        $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $invoice = Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $otherStudent->id
        ]);

        $response = $this->getJson("/api/invoices/student-invoices/{$invoice->id}");

        $response->assertStatus(403)
            ->assertJsonPath('code', 1)
            ->assertJsonPath('message', '您没有权限查看该账单');
    }
}

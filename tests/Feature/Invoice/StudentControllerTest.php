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

        // Create test data
        $this->teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->course = Course::factory()->create([
            'teacher_id' => $this->teacher->id
        ]);
    }

    /**
     * Test student can view their own invoices
     */
    public function test_student_can_view_own_invoices(): void
    {
        Passport::actingAs($this->student);

        // Create 3 invoices
        Invoice::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'sent_at' => now()
        ]);

        // Create another student's invoice
        $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $otherStudent->id,
            'sent_at' => now()
        ]);

        $response = $this->getJson('/api/invoices/student-invoices');

        $response->assertStatus(200)
            ->assertJsonPath('code', 0)
            ->assertJsonCount(3, 'data.data')  // Paginated data in data.data
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
     * Test student can filter invoices by status
     */
    public function test_student_can_filter_invoices_by_status(): void
    {
        Passport::actingAs($this->student);

        // Create invoices with different statuses
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
     * Test student can filter invoices by course keyword
     */
    public function test_student_can_filter_invoices_by_course_keyword(): void
    {
        Passport::actingAs($this->student);

        // Create invoices for math courses
        $mathCourse = Course::factory()->create([
            'name' => '高等数学',
            'teacher_id' => $this->teacher->id
        ]);
        Invoice::factory()->count(2)->create([
            'course_id' => $mathCourse->id,
            'student_id' => $this->student->id,
            'sent_at' => now()
        ]);

        // Create invoices for English courses
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
     * Test student can filter invoices by course year month
     */
    public function test_student_can_filter_invoices_by_course_year_month(): void
    {
        Passport::actingAs($this->student);

        // Create invoices for March 2024 courses
        $marchCourse = Course::factory()->create([
            'year_month' => '2024-03',
            'teacher_id' => $this->teacher->id
        ]);
        Invoice::factory()->count(2)->create([
            'course_id' => $marchCourse->id,
            'student_id' => $this->student->id,
            'sent_at' => now()
        ]);

        // Create invoices for April 2024 courses
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
     * Test student can filter invoices by send time range
     */
    public function test_student_can_filter_invoices_by_send_time_range(): void
    {
        Passport::actingAs($this->student);

        // Create an earlier invoice
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'sent_at' => '2024-03-01 10:00:00'
        ]);

        // Create a later invoice
        Invoice::factory()->create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'sent_at' => '2024-03-15 10:00:00'
        ]);

        // Create an even later invoice
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
     * Test student can view their own invoice detail
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
            ->assertJsonPath('message', 'Get successfully')
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
     * Test student cannot view other students' invoice detail
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
            ->assertJsonPath('message', 'You do not have permission to view this invoice');
    }
}

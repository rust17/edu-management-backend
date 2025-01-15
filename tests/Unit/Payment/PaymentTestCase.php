<?php

namespace Tests\Unit\Payment;

use App\Models\Course;
use App\Models\Invoice;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class PaymentTestCase extends TestCase
{
    use RefreshDatabase;

    protected Invoice $invoice;
    protected User $student;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建测试数据
        $this->student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);

        $this->course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'name' => 'Test Course'
        ]);

        $this->invoice = Invoice::factory()->create([
            'student_id' => $this->student->id,
            'course_id' => $this->course->id,
            'amount' => 1000,
            'no' => 'INV-' . time(),
            'status' => Invoice::STATUS_PENDING
        ]);
    }
}

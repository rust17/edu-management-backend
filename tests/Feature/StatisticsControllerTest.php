<?php

namespace Tests\Feature;

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
     * Test teacher can get statistics
     */
    public function test_teacher_can_get_statistics()
    {
        // Create a teacher user
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Create 3 courses
        $courses = Course::factory()->count(3)->create([
            'teacher_id' => $teacher->id
        ]);

        // Create 2 invoices for each course
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

        // Create another teacher's course and invoice
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $otherCourse = Course::factory()->create(['teacher_id' => $otherTeacher->id]);
        Invoice::factory()->create([
            'course_id' => $otherCourse->id,
            'student_id' => User::factory()->create(['role' => 'student'])->id
        ]);

        // Request statistics
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/teacher-statistics');

        // Verify response
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
                'message' => 'Get successfully',
                'data' => [
                    'course_count' => 3,
                    'invoice_count' => 6
                ]
            ]);
    }

    /**
     * Test student can get statistics
     */
    public function test_student_can_get_statistics()
    {
        // Create a student user
        $student = User::factory()->create(['role' => 'student']);

        // Create 3 courses and associate with student
        $courses = Course::factory()->count(3)->create([
            'teacher_id' => User::factory()->create(['role' => 'teacher'])->id
        ]);
        $courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // Create 2 pending invoices and 1 paid invoice
        $courses->take(2)->each(function ($course) use ($student) {
            Invoice::factory()->create([
                'course_id' => $course->id,
                'student_id' => $student->id,
                'status' => Invoice::STATUS_PENDING,
                'sent_at' => now()
            ]);
        });
        Invoice::factory()->create([
            'course_id' => $courses->last()->id,
            'student_id' => $student->id,
            'status' => Invoice::STATUS_PAID,
            'sent_at' => now()
        ]);

        // Create another student's course and invoice
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherCourse = Course::factory()->create();
        $otherCourse->students()->attach($otherStudent->id);
        Invoice::factory()->create([
            'course_id' => $otherCourse->id,
            'student_id' => $otherStudent->id
        ]);

        // Request statistics
        $response = $this->actingAs($student, 'api')
            ->getJson('/api/student-statistics');

        // Verify response
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
                'message' => 'Get successfully',
                'data' => [
                    'course_count' => 3,
                    'pending_invoice_count' => 2
                ]
            ]);
    }
}

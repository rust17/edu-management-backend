<?php

namespace Tests\Feature\Course;

use App\Models\Course;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class TeacherControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:install');
    }

    /**
     * Test teacher can create course
     */
    public function test_teacher_can_create_course()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $students = User::factory()->count(3)->create([
            'role' => User::ROLE_STUDENT
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->postJson('/api/courses', [
                'name' => 'Math Course',
                'year_month' => '2024-03',
                'fee' => 100,
                'student_ids' => $students->pluck('id')->toArray()
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
                    'students' => [
                        '*' => [
                            'id',
                            'name',
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => 'Course created successfully',
                'data' => [
                    'name' => 'Math Course',
                    'year_month' => '2024-03',
                    'fee' => 100,
                    'teacher_id' => $teacher->id,
                    'students' => $students->map(fn ($student) => $student->only(['id', 'name']))->toArray()
                ]
            ]);
    }

    /**
     * Test student cannot create course
     */
    public function test_student_cannot_create_course()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        $response = $this->actingAs($student, 'api')
            ->postJson('/api/courses', [
                'name' => 'Math Course',
                'year_month' => '2024-03',
                'fee' => 100
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => 'You do not have permission to perform this operation',
                'data' => null
            ]);
    }

    /**
     * Test course creation validation
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
                'message' => 'Validation failed: Course name cannot be empty (and 3 more errors)'
            ])
            ->assertJsonValidationErrors(['name', 'year_month', 'fee'], 'data');
    }

    /**
     * Test teacher can get their courses
     */
    public function test_teacher_can_get_their_courses()
    {
        // Create a teacher user
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Create 3 courses for this teacher
        $teacherCourses = Course::factory()->count(3)->create([
            'teacher_id' => $teacher->id
        ]);

        // Create another teacher and their courses to verify we don't get other teachers' courses
        Course::factory()->count(2)->create([
            'teacher_id' => User::factory()->create(['role' => 'teacher'])
        ]);

        // Simulate teacher login and request course list
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/courses/teacher-courses');

        // Verify response
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data')
            ->assertJsonStructure([
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'year_month',
                            'teacher_id',
                            'fee',
                            'students',
                        ]
                    ]
                ]
            ])
            // Verify returned courses belong to this teacher
            ->assertJson(
                fn (AssertableJson $json) => $json->has('data.data',
                    fn (AssertableJson $json) => $json->each(
                        fn (AssertableJson $json) => $json->where('teacher_id', $teacher->id)->etc()
                    )
                )->etc()
            );
    }

    /**
     * Test teacher can filter courses by keyword
     */
    public function test_teacher_can_filter_courses_by_keyword()
    {
        // Create a teacher user
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Create test course data
        collect(['PHP Course', 'Python Course', 'Java Course'])->each(
            fn ($name) => Course::factory()->create([
                'teacher_id' => $teacher->id,
                'name' => $name
            ])
        );

        // Test keyword filtering
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/courses/teacher-courses?keyword=PHP');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'PHP Course');
    }

    /**
     * Test teacher can filter courses by year month
     */
    public function test_teacher_can_filter_courses_by_year_month()
    {
        // Create a teacher user
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Create different month courses
        collect(['2024-01', '2024-02', '2024-03'])->each(
            fn ($yearMonth) => Course::factory()->create([
                'teacher_id' => $teacher->id,
                'year_month' => $yearMonth
            ])
        );

        // Test year month filtering
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/courses/teacher-courses?year_month=2024-02');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.year_month', '2024-02');
    }

    /**
     * Test teacher can view own course detail
     */
    public function test_teacher_can_view_own_course_detail()
    {
        // Create a teacher user
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Create a course and associate students and invoices
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'name' => 'Test Course',
            'year_month' => '2024-03-01',
            'fee' => 100
        ]);

        // Create students and associate them with the course
        $students = User::factory()->count(3)->create(['role' => 'student']);
        $course->students()->attach($students->pluck('id'));

        // Create an invoice for one of the students
        Invoice::factory()->create([
            'course_id' => $course->id,
            'student_id' => $students->first()->id,
            'status' => 'pending'
        ]);

        // Request course detail
        $response = $this->actingAs($teacher, 'api')
            ->getJson("/api/courses/teacher-courses/{$course->id}");

        // Verify response
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
                    'students' => [
                        '*' => [
                            'id',
                            'name',
                            'invoice_status',
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => 'Get successfully',
                'data' => [
                    'id' => $course->id,
                    'name' => 'Test Course',
                    'year_month' => '2024-03',
                    'fee' => 100,
                    'teacher_id' => $teacher->id,
                    'students' => [
                        [
                            'id' => $students[0]->id,
                            'name' => $students[0]->name,
                            'invoice_status' => 'pending'
                        ]
                    ]
                ]
            ]);

        // Verify returned student data
        $this->assertCount(3, $response->json('data.students'));

        // Verify first student has invoice status
        $this->assertEquals('pending', $response->json('data.students.0.invoice_status'));

        // Verify other students have no invoice status
        $this->assertNull($response->json('data.students.1.invoice_status'));
        $this->assertNull($response->json('data.students.2.invoice_status'));
    }

    /**
     * Test teacher cannot view other teachers' course detail
     */
    public function test_teacher_cannot_view_other_teachers_course_detail()
    {
        // Create two teacher users
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Create course belonging to other teacher
        $course = Course::factory()->create([
            'teacher_id' => User::factory()->create(['role' => 'teacher'])
        ]);

        // Try to view other teacher's course detail
        $response = $this->actingAs($teacher, 'api')
            ->getJson("/api/courses/teacher-courses/{$course->id}");

        // Verify response
        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => 'You can only view your own courses',
                'data' => null
            ]);
    }

    /**
     * Test teacher can update own course
     */
    public function test_teacher_can_update_own_course()
    {
        // Create a teacher user
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Create a course
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'name' => 'Original Course Name',
            'year_month' => '2024-03-01',
            'fee' => 100
        ]);

        // Create some new students
        $newStudents = User::factory()->count(2)->create(['role' => 'student']);

        // Request course update
        $response = $this->actingAs($teacher, 'api')
            ->putJson("/api/courses/{$course->id}", [
                'name' => 'New Course Name',
                'year_month' => '2024-04',
                'fee' => 200,
                'student_ids' => $newStudents->pluck('id')->toArray()
            ]);

        // Verify response
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
                    'students' => [
                        '*' => [
                            'id',
                            'name'
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => 'Course updated successfully',
                'data' => [
                    'id' => $course->id,
                    'name' => 'New Course Name',
                    'year_month' => '2024-04',
                    'fee' => 200,
                    'teacher_id' => $teacher->id
                ]
            ]);

        // Verify database update
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'New Course Name',
            'year_month' => '2024-04-01 00:00:00',
            'fee' => 200
        ]);

        // Verify student association
        $this->assertEquals(
            $newStudents->pluck('id')->toArray(),
            $course->fresh()->students->pluck('id')->toArray()
        );
    }

    /**
     * Test teacher cannot update other teachers' course
     */
    public function test_teacher_cannot_update_other_teachers_course()
    {
        // Create two teacher users
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);

        // Create course belonging to other teacher
        $course = Course::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'name' => 'Original Course Name',
            'year_month' => '2024-03-01',
            'fee' => 100
        ]);

        // Try to update other teacher's course
        $response = $this->actingAs($teacher, 'api')
            ->putJson("/api/courses/{$course->id}", [
                'name' => 'New Course Name',
                'year_month' => '2024-04',
                'fee' => 200
            ]);

        // Verify response
        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => 'You can only edit your own courses',
                'data' => null
            ]);

        // Verify database not updated
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'name' => 'Original Course Name',
            'year_month' => '2024-03-01 00:00:00',
            'fee' => 100
        ]);
    }

    /**
     * Test course update validation
     */
    public function test_course_update_validation()
    {
        // Create a teacher user
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Create a course
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id
        ]);

        // Test empty data
        $response = $this->actingAs($teacher, 'api')
            ->putJson("/api/courses/{$course->id}", []);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 1,
                'message' => 'Validation failed: Course name cannot be empty (and 2 more errors)'
            ])
            ->assertJsonValidationErrors(['name', 'year_month', 'fee'], 'data');

        // Test invalid date format
        $response = $this->actingAs($teacher, 'api')
            ->putJson("/api/courses/{$course->id}", [
                'name' => 'New Course Name',
                'year_month' => 'invalid-date',
                'fee' => 200
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['year_month'], 'data');

        // Test invalid student ID
        $response = $this->actingAs($teacher, 'api')
            ->putJson("/api/courses/{$course->id}", [
                'name' => 'New Course Name',
                'year_month' => '2024-04',
                'fee' => 200,
                'student_ids' => [999999] // Non-existent student ID
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_ids.0'], 'data');
    }
}

<?php

namespace Tests\Feature\Course;

use App\Models\Course;
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
     * Test student can view own courses
     */
    public function test_student_can_view_own_courses()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        // Create some courses and associate to the student
        $courses = Course::factory()->count(3)->create();
        $courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // Create some courses not belonging to the student
        Course::factory()->count(2)->create();

        $response = $this->actingAs($student, 'api')
            ->getJson('/api/courses/student-courses');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => 'Get successfully'
            ])
            ->assertJsonCount(3, 'data.data')
            ->assertJsonStructure([
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'year_month',
                            'fee',
                            'teacher_id',
                            'teacher' => [
                                'id',
                                'name',
                            ],
                            'invoice_status',
                            'invoice_send_at',
                            'paid_at'
                        ]
                    ],
                    'total',
                    'per_page'
                ]
            ]);
    }

    /**
     * Test teacher cannot use the view my courses interface
     */
    public function test_teacher_cannot_view_courses_as_student()
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER
        ]);

        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/courses/student-courses');

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => 'You do not have permission to perform this operation',
                'data' => null
            ]);
    }

    /**
     * Test filter courses by year and month
     */
    public function test_student_can_filter_courses_by_year_month()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        // Create courses for 2024-03
        $march2024Courses = Course::factory()->count(2)->create([
            'year_month' => '2024-03'
        ]);
        $march2024Courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // Create courses for 2024-04
        $april2024Courses = Course::factory()->count(3)->create([
            'year_month' => '2024-04'
        ]);
        $april2024Courses->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        $response = $this->actingAs($student, 'api')
            ->getJson('/api/courses/student-courses?year_month=2024-03');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => 'Get successfully'
            ])
            ->assertJsonCount(2, 'data.data');
    }

    /**
     * Test filter courses by keyword
     */
    public function test_student_can_filter_courses_by_keyword()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        // Create courses containing specific keywords
        Course::factory()->count(2)->create([
            'name' => '高等数学'
        ])->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        // Create other courses
        Course::factory()->count(3)->create([
            'name' => '英语课程'
        ])->each(function ($course) use ($student) {
            $course->students()->attach($student->id);
        });

        $response = $this->actingAs($student, 'api')
            ->getJson('/api/courses/student-courses?keyword=数学');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => 'Get successfully'
            ])
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.name', '高等数学')
            ->assertJsonPath('data.data.1.name', '高等数学');
    }

    /**
     * Test student can view the details of their selected courses
     */
    public function test_student_can_view_own_course_detail()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        $course = Course::factory()->create();
        $course->students()->attach($student->id);

        $response = $this->actingAs($student, 'api')
            ->getJson("/api/courses/student-courses/{$course->id}");

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => 'Get successfully'
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'year_month',
                    'fee',
                    'teacher_id',
                    'teacher' => [
                        'id',
                        'name',
                    ],
                    'invoice_status',
                    'invoice_send_at',
                    'paid_at'
                ]
            ]);
    }

    /**
     * Test student cannot view details of unselected courses
     */
    public function test_student_cannot_view_unselected_course_detail()
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);

        $course = Course::factory()->create();
        // Do not associate student and course

        $response = $this->actingAs($student, 'api')
            ->getJson("/api/courses/student-courses/{$course->id}");

        $response->assertStatus(403)
            ->assertJson([
                'code' => 1,
                'message' => 'You do not have permission to view this course',
                'data' => null
            ]);
    }
}

<?php

namespace Tests\Feature;

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
     * Test teacher can get student list
     */
    public function test_teacher_can_get_student_list()
    {
        // Create a teacher user
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Create some students
        $students = User::factory()->count(3)->create(['role' => 'student']);

        // Request student list
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/students');

        // Verify response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                    ]
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => 'Get successfully',
                'data' => $students->map(fn ($student) => $student->only(['id', 'name']))->toArray()
            ]);
    }

    /**
     * Test teacher can search students by keyword
     */
    public function test_teacher_can_search_students_by_keyword()
    {
        // Create a teacher user
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Create some students with specific names
        User::factory()->create([
            'role' => 'student',
            'name' => 'Zhang San',
            'email' => 'zhangsan@example.com'
        ]);
        User::factory()->create([
            'role' => 'student',
            'name' => 'Li Si',
            'email' => 'lisi@example.com'
        ]);

        // Test search by name
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/students?keyword=Zhang San');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Zhang San');

        // Test search by email
        $response = $this->actingAs($teacher, 'api')
            ->getJson('/api/students?keyword=lisi@example.com');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Li Si');
    }
}

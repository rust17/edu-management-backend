<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:install');
    }

    public function test_teacher_can_login_with_correct_credentials()
    {
        User::factory()->create([
            'email' => 'teacher@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teacher@example.com',
            'password' => 'password123',
            'role' => User::ROLE_TEACHER
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'user'
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => 'Login successful'
            ]);
    }

    public function test_student_can_login_with_correct_credentials()
    {
        User::factory()->create([
            'email' => 'student@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_STUDENT
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'student@example.com',
            'password' => 'password123',
            'role' => User::ROLE_STUDENT
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'user'
                ]
            ])
            ->assertJson([
                'code' => 0,
                'message' => 'Login successful'
            ]);
    }

    public function test_user_cannot_login_with_incorrect_password()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
            'role' => User::ROLE_TEACHER
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'code' => 2,
                'message' => 'Incorrect username or password',
                'data' => null
            ]);
    }

    public function test_user_cannot_login_with_incorrect_type()
    {
        User::factory()->create([
            'email' => 'teacher@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_TEACHER
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teacher@example.com',
            'password' => 'password123',
            'role' => User::ROLE_STUDENT  // Incorrect role
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'code' => 2,
                'message' => 'Incorrect username or password',
                'data' => null
            ]);
    }

    public function test_login_validation_fails_with_missing_fields()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 1,
                'message' => 'Validation failed: Username must be in email format (and 2 more errors)',
            ])
            ->assertJsonValidationErrors(['email', 'password', 'role'], 'data');
    }

    public function test_user_can_logout()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'teacher'
        ]);

        // First login to get a valid token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'teacher'
        ]);

        // Use the valid token to logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $loginResponse->json('data.access_token'),
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => 'Logged out successfully',
                'data' => null
            ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401)
            ->assertJson([
                'code' => 1,
                'message' => 'Unauthenticated.',
                'data' => null
            ]);
    }
}

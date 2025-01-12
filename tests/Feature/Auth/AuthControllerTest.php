<?php

namespace Tests\Feature\Auth;

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
                'access_token',
                'token_type',
                'user'
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
                'access_token',
                'token_type',
                'user'
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
                'message' => '用户名或密码错误'
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
            'role' => User::ROLE_STUDENT  // 错误的类型
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => '用户名或密码错误'
            ]);
    }

    public function test_login_validation_fails_with_missing_fields()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'role']);
    }

    public function test_user_can_logout()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'teacher'
        ]);

        // 先登录获取真实的 token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'teacher'
        ]);

        // 使用真实的 token 进行登出
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $loginResponse->json('access_token'),
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => '已成功登出'
            ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }
}

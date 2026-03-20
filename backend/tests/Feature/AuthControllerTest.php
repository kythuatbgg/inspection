<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_session_payload(): void
    {
        $user = User::factory()->admin()->create([
            'username' => 'test_admin',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'test_admin',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Đăng nhập thành công.')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'username', 'role', 'langPref'],
                    'token',
                ],
            ]);
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        User::factory()->admin()->create([
            'username' => 'test_admin',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'test_admin',
            'password' => 'wrong_password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.',
                'errors' => [],
            ]);
    }

    public function test_login_requires_username(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['username']);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'test_admin',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_me_returns_current_user_inside_data_envelope(): void
    {
        $user = User::factory()->admin()->create([
            'username' => 'test_admin',
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'OK')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.username', 'test_admin')
            ->assertJsonPath('data.user.langPref', 'vn');
    }

    public function test_logout_revokes_token_and_returns_success_message(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Đăng xuất thành công.',
                'data' => [],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}

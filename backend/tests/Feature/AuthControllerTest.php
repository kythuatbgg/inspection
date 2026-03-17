<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test login with valid credentials.
     */
    public function test_login_with_valid_credentials_returns_token(): void
    {
        // Create user
        User::create([
            'name' => 'Test Admin',
            'username' => 'test_admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'lang_pref' => 'vn',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'test_admin',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'username', 'role', 'lang_pref'],
                'token',
            ]);
    }

    /**
     * Test login with invalid credentials.
     */
    public function test_login_with_invalid_credentials_returns_401(): void
    {
        User::create([
            'name' => 'Test Admin',
            'username' => 'test_admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'lang_pref' => 'vn',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'test_admin',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /**
     * Test login with non-existent user.
     */
    public function test_login_with_nonexistent_user_returns_401(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'nonexistent',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /**
     * Test login validation - username required.
     */
    public function test_login_requires_username(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /**
     * Test login validation - password required.
     */
    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'test_admin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test get current user info.
     */
    public function test_me_returns_current_user(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'username' => 'test_admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'lang_pref' => 'vn',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => 'Test Admin',
                'username' => 'test_admin',
                'role' => 'admin',
                'lang_pref' => 'vn',
            ]);
    }

    /**
     * Test logout revokes token.
     */
    public function test_logout_revokes_token(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'username' => 'test_admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'lang_pref' => 'vn',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);

        // Verify token is revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}

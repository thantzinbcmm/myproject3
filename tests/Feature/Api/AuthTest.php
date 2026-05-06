// tests/Feature/Api/AuthTest.php
<?php

namespace Tests\Feature\Api;

use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $role = AdminRole::create([
            'role_name' => 'SUPER_ADMIN',
            'description' => 'Test role',
        ]);

        AdminUser::create([
            'username' => 'testadmin',
            'email' => 'testadmin@test.com',
            'password_hash' => Hash::make('TestPass@123'),
            'last_name' => 'Test',
            'first_name' => 'Admin',
            'role_id' => $role->role_id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/admin/login', [
            'username' => 'testadmin',
            'password' => 'TestPass@123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['accessToken', 'refreshToken', 'admin'],
            ]);
    }

    public function test_admin_login_fails_with_wrong_password(): void
    {
        $role = AdminRole::create([
            'role_name' => 'SUPER_ADMIN',
            'description' => 'Test role',
        ]);

        AdminUser::create([
            'username' => 'testadmin2',
            'email' => 'testadmin2@test.com',
            'password_hash' => Hash::make('CorrectPass@123'),
            'last_name' => 'Test',
            'first_name' => 'Admin',
            'role_id' => $role->role_id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/admin/login', [
            'username' => 'testadmin2',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_admin_login_requires_username_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/admin/login', []);

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}
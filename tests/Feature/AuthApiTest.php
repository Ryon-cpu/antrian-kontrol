<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_and_use_then_revoke_their_token(): void
    {
        User::create([
            'name' => 'Petugas Klinik',
            'username' => 'petugas1',
            'password' => 'password-rahasia',
            'role' => 'petugas',
            'is_active' => true,
        ]);

        $login = $this->postJson('/api/auth/login', [
            'username' => 'petugas1',
            'password' => 'password-rahasia',
        ])->assertOk()
            ->assertJsonPath('user.username', 'petugas1')
            ->assertJsonPath('user.role', 'petugas');

        $token = $login->json('token');
        $this->assertIsString($token);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.username', 'petugas1');

        $this->withToken($token)->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Berhasil keluar.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_credentials_and_inactive_accounts_cannot_login(): void
    {
        User::create([
            'name' => 'Petugas Aktif',
            'username' => 'aktif',
            'password' => 'password-rahasia',
            'role' => 'petugas',
            'is_active' => true,
        ]);
        User::create([
            'name' => 'Petugas Nonaktif',
            'username' => 'nonaktif',
            'password' => 'password-rahasia',
            'role' => 'petugas',
            'is_active' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'username' => 'aktif',
            'password' => 'password-salah',
        ])->assertUnauthorized();

        $this->postJson('/api/auth/login', [
            'username' => 'nonaktif',
            'password' => 'password-rahasia',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_bearer_token_cannot_access_authenticated_routes(): void
    {
        $this->withToken('invalid-token')->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_admin_routes_are_forbidden_to_petugas(): void
    {
        $petugas = User::create([
            'name' => 'Petugas',
            'username' => 'petugas1',
            'password' => 'password-rahasia',
            'role' => 'petugas',
            'is_active' => true,
        ]);
        $petugasToken = $petugas->createToken('test', ['petugas'])->plainTextToken;

        $this->withToken($petugasToken)->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'username' => 'admin1',
            'password' => 'password-rahasia',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $adminToken = $admin->createToken('test', ['admin'])->plainTextToken;

        $this->withToken($adminToken)->getJson('/api/admin/users')->assertOk();
    }
}

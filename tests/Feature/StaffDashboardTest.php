<?php

namespace Tests\Feature;

use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_staff_dashboards_or_read_patient_records(): void
    {
        QueueEntry::create([
            'queue_date' => today()->toDateString(),
            'number' => 1,
            'position' => 1,
            'device_hash' => hash('sha256', 'staff-dashboard-test-device'),
            'patient_name' => 'Pasien Rahasia',
            'patient_age' => 70,
            'patient_address' => 'Alamat Rahasia',
            'complaint' => 'Keluhan Rahasia',
            'status' => 'waiting',
        ]);

        $this->get('/petugas')
            ->assertRedirect('/login');

        $this->get('/dashboard')->assertRedirect('/login');

        $this->getJson('/api/petugas/queue')->assertUnauthorized();
    }

    public function test_authenticated_staff_sees_the_queue_dashboard_inside_breeze(): void
    {
        $staff = User::create([
            'name' => 'Petugas Klinik',
            'email' => 'petugas@example.test',
            'password' => 'password-rahasia',
            'role' => 'petugas',
            'is_active' => true,
        ]);

        $this->actingAs($staff)->get('/petugas')
            ->assertOk()
            ->assertSee('Dashboard petugas')
            ->assertSee('id="staff-dashboard-root"', false)
            ->assertSee('data-dashboard-config=', false)
            ->assertSee('data-auth-mode="token"', false);

        $this->actingAs($staff)->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Antrian')
            ->assertSee('id="staff-dashboard-root"', false)
            ->assertSee('data-dashboard-config=', false)
            ->assertSee('data-auth-mode="session"', false)
            ->assertDontSee('Masuk petugas');
    }

    public function test_guests_and_inactive_staff_cannot_open_the_breeze_queue_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $inactiveStaff = User::create([
            'name' => 'Petugas Nonaktif',
            'email' => 'nonaktif@example.test',
            'password' => 'password-rahasia',
            'role' => 'petugas',
            'is_active' => false,
        ]);

        $this->actingAs($inactiveStaff)->get('/petugas')->assertForbidden();
        $this->actingAs($inactiveStaff)->get('/dashboard')->assertForbidden();
    }

    public function test_active_admin_can_open_the_staff_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password' => 'password-rahasia',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/petugas')->assertOk();
    }

    public function test_admin_sees_a_separate_dashboard_from_petugas(): void
    {
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin-dashboard@example.test',
            'password' => 'password-rahasia',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Panel administrasi klinik')
            ->assertSee('Buka dashboard petugas &amp; riwayat pasien', false)
            ->assertDontSee('Dashboard petugas')
            ->assertDontSee('Panggil berikutnya');
    }

    public function test_role_middleware_accepts_a_plain_string_role_value(): void
    {
        $staff = User::create([
            'name' => 'Petugas Klinik',
            'email' => 'petugas-string@example.test',
            'password' => 'password-rahasia',
            'role' => 'petugas',
            'is_active' => true,
        ]);
        $staff->mergeCasts(['role' => 'string']);

        $this->actingAs($staff)->get('/dashboard')->assertOk();
    }

    public function test_role_middleware_forbids_an_active_user_with_a_null_role(): void
    {
        $staff = User::create([
            'name' => 'Petugas Tanpa Role',
            'email' => 'petugas-null-role@example.test',
            'password' => 'password-rahasia',
            'role' => 'petugas',
            'is_active' => true,
        ]);
        $staff->role = null;

        $this->actingAs($staff)->get('/dashboard')->assertForbidden();
    }

    public function test_breeze_session_authenticates_the_staff_api(): void
    {
        $staff = User::create([
            'name' => 'Petugas Klinik',
            'email' => 'petugas@example.test',
            'password' => 'password-rahasia',
            'role' => 'petugas',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->withHeader('Referer', 'http://localhost:8000/dashboard')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.name', 'Petugas Klinik')
            ->assertJsonPath('user.role', 'petugas');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}

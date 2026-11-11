<?php

namespace Tests\Feature;

use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_staff_login_shell_but_cannot_read_patient_records(): void
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
            ->assertOk()
            ->assertSee('Masuk petugas')
            ->assertSee('Antrian hari ini')
            ->assertSee('data-auth-mode="token"', false)
            ->assertDontSee('Pasien Rahasia')
            ->assertDontSee('Alamat Rahasia')
            ->assertDontSee('Keluhan Rahasia');

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

        $this->actingAs($staff)->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Antrian')
            ->assertSee('Antrian hari ini')
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

        $this->actingAs($inactiveStaff)->get('/dashboard')->assertForbidden();
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

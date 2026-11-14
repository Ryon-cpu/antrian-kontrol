<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_an_admin_and_default_settings_idempotently(): void
    {
        config(['antrian.admin_password' => 'a-strong-test-password']);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'admin')->sole();

        $this->assertSame('Administrator', $admin->name);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('a-strong-test-password', $admin->password));
        $this->assertSame(12, Setting::count());
        $this->assertSame('0', Setting::findOrFail('is_open')->value);
        $this->assertSame('Klinik Sehat Contoh', Setting::findOrFail('place_name')->value);
        $this->assertSame('Jl. Kesehatan No. 1, Kota Contoh', Setting::findOrFail('place_address')->value);
        $this->assertSame('Jam pelayanan diatur langsung oleh petugas.', Setting::findOrFail('practice_hours_note')->value);
        $this->assertSame('Dokter Contoh', Setting::findOrFail('doctor_name')->value);
        $this->assertSame('', Setting::findOrFail('doctor_title')->value);
        $this->assertSame('Profil dokter contoh. Ganti informasi ini melalui pengaturan aplikasi sebelum digunakan.', Setting::findOrFail('doctor_bio')->value);
        $this->assertDatabaseHas('doctor_achievements', ['title' => 'Contoh aktivitas dokter']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Klinik Sehat Contoh')
            ->assertSee('Dokter Contoh')
            ->assertDontSee('STR:')
            ->assertDontSee('IDI Cabang')
            ->assertDontSee('Kabupaten Sumenep');
    }
}

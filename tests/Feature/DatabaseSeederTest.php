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
    }
}

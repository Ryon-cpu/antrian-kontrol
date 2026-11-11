<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $configuredPassword = config('antrian.admin_password');
        $password = $configuredPassword ?: Str::password(16, symbols: false);

        $admin = User::firstOrNew(['username' => 'admin']);
        if (! $admin->exists) {
            $admin->fill([
                'name' => 'Administrator',
                'password' => $password,
                'role' => 'admin',
                'is_active' => true,
            ])->save();

            if (! $configuredPassword) {
                $this->command?->warn("Password admin dibuat acak (catat sekarang): {$password}");
            }
        }

        $defaults = [
            'app_name' => 'Antrian Pasien',
            'place_type' => 'klinik',
            'place_name' => 'Nama Tempat',
            'place_address' => null,
            'place_phone' => null,
            'place_description' => null,
            'practice_hours_note' => null,
            'doctor_name' => null,
            'doctor_title' => null,
            'doctor_bio' => null,
            'doctor_photo' => null,
            'is_open' => '0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

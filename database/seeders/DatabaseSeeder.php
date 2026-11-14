<?php

namespace Database\Seeders;

use App\Models\DoctorAchievement;
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
            'place_name' => 'Klinik Sehat Contoh',
            'place_address' => 'Jl. Kesehatan No. 1, Kota Contoh',
            'place_phone' => null,
            'place_description' => 'Tempat praktik contoh untuk pengembangan dan pengujian aplikasi.',
            'practice_hours_note' => 'Jam pelayanan diatur langsung oleh petugas.',
            'doctor_name' => 'Dokter Contoh',
            'doctor_title' => '',
            'doctor_bio' => 'Profil dokter contoh. Ganti informasi ini melalui pengaturan aplikasi sebelum digunakan.',
            'doctor_photo' => null,
            'is_open' => '0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $achievements = [
            [
                'title' => 'Contoh aktivitas dokter',
                'description' => 'Informasi kegiatan dokter contoh untuk pengembangan dan pengujian aplikasi.',
                'sort_order' => 1,
            ],
            [
                'title' => 'Contoh edukasi kesehatan',
                'description' => 'Contoh informasi edukasi kesehatan yang dapat diganti oleh admin.',
                'sort_order' => 2,
            ],
        ];

        foreach ($achievements as $ach) {
            DoctorAchievement::firstOrCreate(['title' => $ach['title']], $ach);
        }
    }
}

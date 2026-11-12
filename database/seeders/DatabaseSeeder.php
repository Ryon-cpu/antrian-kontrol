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
            'app_name' => 'DPM dr. Susianto',
            'place_type' => 'klinik',
            'place_name' => 'DPM dr. Susianto',
            'place_address' => 'VJQX+7X7, Jl. Raya Pamekasan - Sumenep, Dusun Ponjun, Desa Jaddung, Kecamatan Pragaan, Kabupaten Sumenep, Jawa Timur 69465',
            'place_phone' => null,
            'place_description' => 'Dokter Praktik Mandiri (Pelayanan Medis Umum). Tersedia area tunggu yang nyaman dan fasilitas toilet untuk pasien dan pendamping pasien.',
            'practice_hours_note' => 'Senin - Jumat (Sesuai jam operasional harian. Sabtu & Minggu libur)',
            'doctor_name' => 'dr. S. Susianto',
            'doctor_title' => 'M.Si.',
            'doctor_bio' => 'Dokter Praktik Mandiri (Pelayanan Medis Umum) yang berdedikasi melayani masyarakat di wilayah Pragaan dan sekitarnya. Terdaftar resmi sebagai anggota IDI Cabang Sumenep dengan riwayat nomor registrasi STR: 3411100316065696. Berpengalaman di bidang manajemen dan kepemimpinan pelayanan kesehatan daerah, pernah menjabat sebagai Direktur RSUD dr. H. Moh. Anwar Sumenep serta Kepala Dinas Kesehatan Kabupaten Sumenep. Dikenal aktif dalam edukasi pelayanan kesehatan masyarakat dan pemberdayaan kesehatan lingkungan di wilayah Pragaan serta pesantren setempat.',
            'doctor_photo' => null,
            'is_open' => '0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $achievements = [
            [
                'title' => 'Anggota IDI Cabang Sumenep',
                'description' => 'Terdaftar aktif sebagai anggota Ikatan Dokter Indonesia (IDI) Cabang Sumenep (Nomor STR: 3411100316065696).',
                'sort_order' => 1,
            ],
            [
                'title' => 'Mantan Direktur RSUD dr. H. Moh. Anwar Sumenep',
                'description' => 'Pernah mengemban amanah sebagai Direktur RSUD dr. H. Moh. Anwar Sumenep dalam peningkatan mutu fasilitas dan pelayanan rujukan masyarakat.',
                'sort_order' => 2,
            ],
            [
                'title' => 'Mantan Kepala Dinas Kesehatan Kabupaten Sumenep',
                'description' => 'Pernah memimpin Dinas Kesehatan Kabupaten Sumenep dalam mengawal kebijakan kesehatan publik daerah dan penguatan layanan puskesmas.',
                'sort_order' => 3,
            ],
            [
                'title' => 'Edukasi & Pemberdayaan Kesehatan Masyarakat',
                'description' => 'Aktif dalam edukasi pelayanan kesehatan masyarakat serta pemberdayaan kesehatan di wilayah Pragaan dan lingkungan pondok pesantren.',
                'sort_order' => 4,
            ],
        ];

        foreach ($achievements as $ach) {
            \App\Models\DoctorAchievement::firstOrCreate(['title' => $ach['title']], $ach);
        }
    }
}

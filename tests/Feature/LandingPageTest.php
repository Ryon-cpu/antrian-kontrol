<?php

namespace Tests\Feature;

use App\Models\DoctorAchievement;
use App\Models\QueueEntry;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_registration_before_clinic_information(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 11)->setTime(8, 0));
        Setting::updateOrCreate(['key' => 'app_name'], ['value' => 'Klinik Sehat']);
        Setting::updateOrCreate(['key' => 'place_name'], ['value' => 'Puskesmas Melati']);
        Setting::updateOrCreate(['key' => 'place_address'], ['value' => 'Jl. Mawar No. 5']);
        Setting::updateOrCreate(['key' => 'doctor_name'], ['value' => 'Dr. Sari']);
        Setting::updateOrCreate(['key' => 'doctor_title'], ['value' => 'Sp.PD']);
        Setting::updateOrCreate(['key' => 'is_open'], ['value' => '1']);
        DoctorAchievement::create([
            'title' => 'Dokter teladan',
            'description' => 'Penghargaan pelayanan kesehatan',
            'sort_order' => 1,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Daftarkan pasien', 'Puskesmas Melati', 'Informasi dokter', 'Prestasi dokter'])
            ->assertSee('Klinik Sehat')
            ->assertSee('Dr. Sari Sp.PD')
            ->assertSee('Dokter teladan')
            ->assertSee('data-practice-open="true"', false);
    }

    public function test_landing_page_escapes_public_information_and_disables_registration_when_closed(): void
    {
        Setting::updateOrCreate(['key' => 'place_name'], ['value' => '<script>alert(1)</script>']);

        $this->get('/')
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('data-practice-open="false"', false)
            ->assertSee('Pendaftaran akan dibuka oleh petugas.');
    }

    public function test_landing_page_disables_registration_when_daily_queue_is_full(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 11)->setTime(8, 0));
        config(['antrian.max_number' => 1]);
        Setting::updateOrCreate(['key' => 'is_open'], ['value' => '1']);
        QueueEntry::create([
            'queue_date' => today()->toDateString(),
            'number' => 1,
            'position' => 1,
            'device_hash' => hash('sha256', 'existing-device'),
            'patient_name' => 'Pasien Tersimpan',
            'patient_age' => 65,
            'patient_address' => 'Alamat privat',
            'complaint' => 'Keluhan privat',
            'status' => 'waiting',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-queue-full="true"', false)
            ->assertSee('Antrian hari ini penuh.');
    }

    public function test_landing_page_does_not_display_registered_queue_data(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 11)->setTime(8, 0));
        Setting::updateOrCreate(['key' => 'is_open'], ['value' => '1']);
        QueueEntry::create([
            'queue_date' => today()->toDateString(),
            'number' => 1,
            'position' => 1,
            'device_hash' => hash('sha256', 'existing-device'),
            'patient_name' => 'Pasien Tersimpan',
            'patient_age' => 65,
            'patient_address' => 'Alamat privat',
            'complaint' => 'Keluhan privat',
            'status' => 'waiting',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="registration-form"', false)
            ->assertSee('Nama pasien')
            ->assertDontSee('Antrian perangkat ini')
            ->assertDontSee('Sisa antrian di depan')
            ->assertDontSee('Pasien Tersimpan')
            ->assertDontSee('Nomor 01');
    }

    public function test_vapid_public_key_is_available_on_queue_page_and_private_key_is_not_exposed(): void
    {
        config([
            'antrian.vapid.public' => 'public-vapid-key',
            'antrian.vapid.private' => 'private-vapid-key',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertDontSee('public-vapid-key')
            ->assertDontSee('private-vapid-key');

        $this->get('/antrian')
            ->assertOk()
            ->assertSee('data-vapid-public-key="public-vapid-key"', false)
            ->assertDontSee('private-vapid-key');
    }
}

<?php

use App\Models\QueueEntry;
use App\Models\User;

it('requires a staff or admin account to read patient history', function () {
    $this->getJson('/api/petugas/queue/history')->assertUnauthorized();

    $user = User::create([
        'name' => 'Petugas Tanpa Role',
        'username' => 'petugas-tanpa-role',
        'password' => 'password-rahasia',
        'role' => 'petugas',
        'is_active' => true,
    ]);
    $user->role = null;

    $this->actingAs($user)
        ->getJson('/api/petugas/queue/history')
        ->assertForbidden();
});

it('returns filtered and paginated patient records within the available history', function () {
    $this->travelTo('2026-11-30 12:00:00');

    $admin = User::create([
        'name' => 'Administrator',
        'username' => 'admin-history',
        'password' => 'password-rahasia',
        'role' => 'admin',
        'is_active' => true,
    ]);

    $createEntry = function (string $date, int $number, string $status, string $name, string $complaint): QueueEntry {
        return QueueEntry::create([
            'queue_date' => $date,
            'number' => $number,
            'position' => $number,
            'device_hash' => hash('sha256', $date.$number),
            'patient_name' => $name,
            'patient_age' => 60,
            'patient_address' => 'Jl. Mawar No. 1',
            'complaint' => $complaint,
            'status' => $status,
        ]);
    };

    $createEntry('2026-11-30', 1, 'done', 'Pasien Budi', 'Batuk sejak kemarin');
    $createEntry('2026-11-29', 1, 'done', 'Pasien Siti', 'Batuk dan demam');
    $createEntry('2026-11-30', 2, 'waiting', 'Pasien Dedi', 'Batuk sejak kemarin');
    $createEntry('2026-10-31', 1, 'done', 'Pasien Lama', 'Batuk sejak kemarin');

    $this->actingAs($admin)
        ->getJson('/api/petugas/queue/history?from=2026-11-01&to=2026-11-30&status=done&search=Batuk&per_page=1')
        ->assertOk()
        ->assertJsonPath('filters.from', '2026-11-01')
        ->assertJsonPath('filters.to', '2026-11-30')
        ->assertJsonPath('filters.available_from', '2026-11-01')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('data.0.queue_date', '2026-11-30')
        ->assertJsonPath('data.0.patient.name', 'Pasien Budi')
        ->assertDontSee('Pasien Dedi')
        ->assertDontSee('Pasien Lama');
});

it('rejects history dates outside the last 30 days or after today', function (array $query, string $field, string $message) {
    $this->travelTo('2026-11-30 12:00:00');

    $staff = User::create([
        'name' => 'Petugas Klinik',
        'username' => 'petugas-history',
        'password' => 'password-rahasia',
        'role' => 'petugas',
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->getJson('/api/petugas/queue/history?'.http_build_query($query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field)
        ->assertJsonPath("errors.{$field}.0", $message);
})->with([
    'start before the 30-day window' => [
        ['from' => '2026-10-31'],
        'from',
        'Riwayat hanya tersedia untuk 30 hari terakhir.',
    ],
    'end after today' => [
        ['to' => '2026-12-01'],
        'to',
        'Tanggal akhir tidak boleh melewati hari ini.',
    ],
    'end before start' => [
        ['from' => '2026-11-20', 'to' => '2026-11-19'],
        'to',
        'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
    ],
]);

it('limits the number of records returned per history page', function () {
    $staff = User::create([
        'name' => 'Petugas Klinik',
        'username' => 'petugas-history-limit',
        'password' => 'password-rahasia',
        'role' => 'petugas',
        'is_active' => true,
    ]);

    $this->actingAs($staff)
        ->getJson('/api/petugas/queue/history?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page')
        ->assertJsonPath('errors.per_page.0', 'Jumlah data per halaman maksimal 100.');
});

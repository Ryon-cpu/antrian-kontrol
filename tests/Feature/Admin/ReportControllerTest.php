<?php

use App\Models\QueueEntry;
use App\Models\QueueEvent;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('admin daily report includes status counts and patients ever skipped', function () {
    $admin = User::create([
        'name' => 'Administrator',
        'username' => 'admin-report',
        'password' => 'password-rahasia',
        'role' => 'admin',
        'is_active' => true,
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $createEntry = function (string $date, int $number, string $status): QueueEntry {
        return QueueEntry::create([
            'queue_date' => $date,
            'number' => $number,
            'position' => $number,
            'device_hash' => hash('sha256', $date.$number),
            'patient_name' => 'Identitas Rahasia',
            'patient_age' => 60,
            'patient_address' => 'Alamat Rahasia',
            'complaint' => 'Keluhan Rahasia',
            'status' => $status,
        ]);
    };

    $createEntry('2026-11-14', 1, 'waiting');
    $createEntry('2026-11-14', 2, 'called');
    $createEntry('2026-11-14', 3, 'done');
    $createEntry('2026-11-14', 4, 'cancelled');
    $skippedEntry = $createEntry('2026-11-14', 5, 'waiting');
    $previousDayEntry = $createEntry('2026-11-13', 1, 'waiting');

    QueueEvent::create([
        'queue_entry_id' => $skippedEntry->id,
        'event_type' => 'skipped',
    ]);
    QueueEvent::create([
        'queue_entry_id' => $skippedEntry->id,
        'event_type' => 'skipped',
    ]);
    QueueEvent::create([
        'queue_entry_id' => $previousDayEntry->id,
        'event_type' => 'skipped',
    ]);

    $this->getJson('/api/admin/reports/daily?from=2026-11-14&to=2026-11-14')
        ->assertOk()
        ->assertJsonPath('from', '2026-11-14')
        ->assertJsonPath('to', '2026-11-14')
        ->assertJsonPath('data.0.date', '2026-11-14')
        ->assertJsonPath('data.0.total', 5)
        ->assertJsonPath('data.0.waiting', 2)
        ->assertJsonPath('data.0.called', 1)
        ->assertJsonPath('data.0.done', 1)
        ->assertJsonPath('data.0.cancelled', 1)
        ->assertJsonPath('data.0.unfinished', 3)
        ->assertJsonPath('data.0.skipped', 1)
        ->assertDontSee('Identitas Rahasia')
        ->assertDontSee('Alamat Rahasia')
        ->assertDontSee('Keluhan Rahasia');
});

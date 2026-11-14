<?php

namespace Tests\Feature;

use App\Models\QueueEntry;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyQueueResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_reset_closes_practice_and_cancels_only_active_queues_from_previous_days(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        Carbon::setTestNow('2026-11-12 00:05:00');
        Setting::create(['key' => 'is_open', 'value' => '1']);

        $yesterdayWaiting = $this->createEntry('2026-11-11', 'waiting');
        $yesterdayCalled = $this->createEntry('2026-11-10', 'called');
        $yesterdayDone = $this->createEntry('2026-11-11', 'done');
        $todayWaiting = $this->createEntry('2026-11-12', 'waiting');

        $this->artisan('queue:daily-reset')
            ->expectsOutput('Praktek ditutup. Antrian kemarin dibatalkan: 2')
            ->assertSuccessful();

        $this->assertSame('cancelled', $yesterdayWaiting->fresh()->status->value);
        $this->assertNotNull($yesterdayWaiting->fresh()->finished_at);
        $this->assertSame('cancelled', $yesterdayCalled->fresh()->status->value);
        $this->assertSame('done', $yesterdayDone->fresh()->status->value);
        $this->assertSame('waiting', $todayWaiting->fresh()->status->value);
        $this->assertSame('0', Setting::findOrFail('is_open')->value);
    }

    public function test_daily_reset_and_call_timeouts_are_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('queue:daily-reset')
            ->expectsOutputToContain('queue:purge-old')
            ->expectsOutputToContain('queue:process-timeouts')
            ->assertSuccessful();
    }

    private function createEntry(string $date, string $status): QueueEntry
    {
        $number = QueueEntry::where('queue_date', $date)->count() + 1;

        return QueueEntry::create([
            'queue_date' => $date,
            'number' => $number,
            'position' => $number,
            'device_hash' => hash('sha256', $date.$status.$number),
            'patient_name' => 'Pasien',
            'patient_age' => 60,
            'patient_address' => 'Alamat',
            'complaint' => 'Pemeriksaan',
            'status' => $status,
        ]);
    }
}

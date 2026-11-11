<?php

namespace App\Console\Commands;

use App\Enums\QueueStatus;
use App\Models\QueueEntry;
use App\Services\QueueService;
use Illuminate\Console\Command;

class DailyQueueReset extends Command
{
    protected $signature = 'queue:daily-reset';

    protected $description = 'Tutup praktek dan batalkan antrian aktif dari hari sebelumnya.';

    public function handle(QueueService $queue): int
    {
        $queue->closePractice();

        $cancelled = QueueEntry::where('queue_date', '<', today()->toDateString())
            ->whereIn('status', QueueStatus::activeValues())
            ->update(['status' => QueueStatus::Cancelled->value, 'finished_at' => now()]);

        $this->info("Praktek ditutup. Antrian kemarin dibatalkan: {$cancelled}");

        return self::SUCCESS;
    }
}

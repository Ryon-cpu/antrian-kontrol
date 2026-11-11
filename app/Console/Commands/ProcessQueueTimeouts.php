<?php

namespace App\Console\Commands;

use App\Services\QueueService;
use Illuminate\Console\Command;

class ProcessQueueTimeouts extends Command
{
    protected $signature = 'queue:process-timeouts';

    protected $description = 'Geser otomatis pasien yang sudah dipanggil 3x dan tidak hadir setelah jeda.';

    public function handle(QueueService $queue): int
    {
        $count = $queue->autoSkipExpired();
        $this->info("Antrian digeser otomatis: {$count}");

        return self::SUCCESS;
    }
}

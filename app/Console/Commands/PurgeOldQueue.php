<?php

namespace App\Console\Commands;

use App\Services\QueueService;
use Illuminate\Console\Command;

class PurgeOldQueue extends Command
{
    protected $signature = 'queue:purge-old';

    protected $description = 'Hapus data antrian/pasien yang melewati masa simpan.';

    public function handle(QueueService $queue): int
    {
        $deleted = $queue->purgeOld();
        $this->info("Data antrian dihapus: {$deleted}");

        return self::SUCCESS;
    }
}

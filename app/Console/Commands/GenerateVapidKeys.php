<?php

namespace App\Console\Commands;

use Base64Url\Base64Url;
use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
use Symfony\Component\Process\Process;
use Throwable;

class GenerateVapidKeys extends Command
{
    protected $signature = 'push:vapid';

    protected $description = 'Buat pasangan kunci VAPID untuk Web Push. Salin hasilnya ke .env.';

    public function handle(): int
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (Throwable) {
            $keys = $this->createKeysWithOpenSslCli();
            if ($keys === null) {
                return self::FAILURE;
            }
        }

        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->newLine();
        $this->warn('Simpan kunci privat dengan aman dan jangan di-commit ke Git.');

        return self::SUCCESS;
    }

    /**
     * @return array{publicKey: string, privateKey: string}|null
     */
    private function createKeysWithOpenSslCli(): ?array
    {
        $pemPath = tempnam(sys_get_temp_dir(), 'vapid-');
        if ($pemPath === false) {
            $this->error('Tidak dapat membuat file sementara untuk kunci VAPID.');

            return null;
        }

        try {
            $process = new Process(['openssl', 'ecparam', '-name', 'prime256v1', '-genkey', '-noout', '-out', $pemPath]);
            $process->run();
            if (! $process->isSuccessful()) {
                $this->error('Gagal membuat kunci VAPID menggunakan OpenSSL CLI. Pastikan OpenSSL tersedia di PATH.');

                return null;
            }

            $keys = VAPID::validate([
                'subject' => 'mailto:admin@example.com',
                'pemFile' => $pemPath,
            ]);

            return [
                'publicKey' => Base64Url::encode($keys['publicKey']),
                'privateKey' => Base64Url::encode($keys['privateKey']),
            ];
        } catch (Throwable $exception) {
            $this->error('Gagal membaca kunci VAPID yang dihasilkan: '.$exception->getMessage());

            return null;
        } finally {
            if (file_exists($pemPath)) {
                unlink($pemPath);
            }
        }
    }
}

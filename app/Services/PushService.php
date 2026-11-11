<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class PushService
{
    /**
     * Kirim Web Push ke semua langganan milik satu perangkat pendamping.
     * Tidak pernah melempar error supaya alur antrian tidak ikut gagal.
     *
     * @param  array<string, mixed>  $payload
     */
    public function sendToDevice(string $deviceHash, array $payload): void
    {
        $subscriptions = PushSubscription::where('device_hash', $deviceHash)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $vapid = config('antrian.vapid');
        if (empty($vapid['public']) || empty($vapid['private'])) {
            Log::warning('Web Push dilewati: VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY belum diisi.');

            return;
        }

        try {
            $webPush = new WebPush(
                ['VAPID' => [
                    'subject' => $vapid['subject'],
                    'publicKey' => $vapid['public'],
                    'privateKey' => $vapid['private'],
                ]],
                ['TTL' => 300, 'urgency' => 'high'],
                10
            );

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'contentEncoding' => 'aes128gcm',
                        'keys' => ['p256dh' => $sub->p256dh, 'auth' => $sub->auth],
                    ]),
                    json_encode($payload, JSON_UNESCAPED_UNICODE)
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
                    continue;
                }

                if (! $report->isSuccess()) {
                    Log::warning('Web Push tidak terkirim.', [
                        'reason' => $report->getReason(),
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::error('Web Push gagal.', [
                'exception_type' => $e::class,
            ]);
        }
    }
}

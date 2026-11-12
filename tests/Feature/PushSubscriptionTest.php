<?php

namespace Tests\Feature;

use App\Models\QueueEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_companion_can_create_and_update_a_push_subscription(): void
    {
        $token = str_repeat('d', 64);
        $this->createQueueEntry($token);
        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/subscription-token',
            'keys' => [
                'p256dh' => 'public-key',
                'auth' => 'auth-key',
            ],
        ];

        $this->withHeader('X-Device-Token', $token)
            ->postJson('/api/companion/push-subscription', $payload)
            ->assertCreated()
            ->assertJsonPath('message', 'Notifikasi diaktifkan.');

        $this->assertDatabaseHas('push_subscriptions', [
            'device_hash' => hash('sha256', $token),
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
            'p256dh' => 'public-key',
            'auth' => 'auth-key',
        ]);

        $payload['keys']['p256dh'] = 'updated-public-key';
        $this->withHeader('X-Device-Token', $token)
            ->postJson('/api/companion/push-subscription', $payload)
            ->assertCreated();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
            'p256dh' => 'updated-public-key',
        ]);
    }

    public function test_push_subscription_requires_a_known_device_and_supported_secure_endpoint(): void
    {
        $this->withHeader('X-Device-Token', str_repeat('x', 64))
            ->postJson('/api/companion/push-subscription', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/subscription-token',
                'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-key'],
            ])
            ->assertUnauthorized();

        $token = str_repeat('d', 64);
        $this->createQueueEntry($token);

        $this->withHeader('X-Device-Token', $token)
            ->postJson('/api/companion/push-subscription', [
                'endpoint' => 'http://fcm.googleapis.com/fcm/send/subscription-token',
                'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-key'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');

        $this->withHeader('X-Device-Token', $token)
            ->postJson('/api/companion/push-subscription', [
                'endpoint' => 'https://127.0.0.1/internal',
                'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-key'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    private function createQueueEntry(string $token): void
    {
        QueueEntry::create([
            'queue_date' => today()->toDateString(),
            'number' => 1,
            'position' => 1,
            'device_hash' => hash('sha256', $token),
            'patient_name' => 'Pasien Uji',
            'patient_age' => 50,
            'patient_address' => 'Alamat Uji',
            'complaint' => 'Pemeriksaan',
            'status' => 'waiting',
        ]);
    }
}

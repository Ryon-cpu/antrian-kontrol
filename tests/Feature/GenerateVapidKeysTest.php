<?php

namespace Tests\Feature;

use Tests\TestCase;

class GenerateVapidKeysTest extends TestCase
{
    public function test_vapid_command_generates_a_public_and_private_key_pair(): void
    {
        $this->artisan('push:vapid')
            ->expectsOutputToContain('VAPID_PUBLIC_KEY=')
            ->expectsOutputToContain('VAPID_PRIVATE_KEY=')
            ->assertSuccessful();
    }
}

<?php

namespace Tests\Feature;

use App\Models\QueueEntry;
use App\Models\User;
use App\Services\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QueueFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-11 08:00:00'); // Rabu
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function actAsPetugas(): void
    {
        $user = User::create([
            'name' => 'Petugas',
            'username' => 'petugas1',
            'password' => 'rahasia123',
            'role' => 'petugas',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user, ['petugas']);
    }

    private function openPractice(): void
    {
        $this->actAsPetugas();
        $this->postJson('/api/petugas/practice/open')->assertOk();
    }

    private function registerPatient(?string $token = null, string $name = 'Bapak Slamet')
    {
        $headers = $token ? ['X-Device-Token' => $token] : [];

        return $this->withHeaders($headers)->postJson('/api/companion/register', [
            'patient_name' => $name,
            'patient_age' => 72,
            'patient_address' => 'Jl. Mawar No. 1',
            'complaint' => 'Batuk dan demam',
        ]);
    }

    public function test_registration_rejected_when_practice_closed(): void
    {
        $this->registerPatient()->assertStatus(423);
    }

    public function test_numbers_start_at_01_and_increase(): void
    {
        $this->openPractice();

        $this->registerPatient()->assertCreated()->assertJsonPath('entry.number_label', '01');
        $this->registerPatient()->assertCreated()->assertJsonPath('entry.number_label', '02');
    }

    public function test_numbers_do_not_reuse_on_the_same_day_but_reset_on_next_day(): void
    {
        $this->openPractice();

        $this->registerPatient(null, 'Pasien hari ini 1')->assertCreated()->assertJsonPath('entry.number_label', '01');
        $this->registerPatient(null, 'Pasien hari ini 2')->assertCreated()->assertJsonPath('entry.number_label', '02');
        $this->registerPatient(null, 'Pasien hari ini 3')->assertCreated()->assertJsonPath('entry.number_label', '03');

        $this->travelTo('2026-11-12 08:00:00');

        $this->registerPatient(null, 'Pasien besok 1')->assertCreated()->assertJsonPath('entry.number_label', '01');
    }

    public function test_deleted_queue_number_is_not_reused_on_the_same_day(): void
    {
        $this->openPractice();
        $this->registerPatient()->assertCreated()->assertJsonPath('entry.number_label', '01');
        $this->registerPatient()->assertCreated()->assertJsonPath('entry.number_label', '02');
        $this->registerPatient()->assertCreated()->assertJsonPath('entry.number_label', '03');

        QueueEntry::where('number', 3)->firstOrFail()->delete();

        $this->registerPatient()
            ->assertCreated()
            ->assertJsonPath('entry.number_label', '04');
    }

    public function test_registration_stays_full_after_the_highest_issued_number_is_deleted(): void
    {
        config(['antrian.max_number' => 2]);
        $this->openPractice();
        $this->registerPatient()->assertCreated()->assertJsonPath('entry.number_label', '01');
        $this->registerPatient()->assertCreated()->assertJsonPath('entry.number_label', '02');

        QueueEntry::where('number', 2)->firstOrFail()->delete();

        $this->registerPatient()
            ->assertStatus(409)
            ->assertJsonPath('message', 'Antrian hari ini penuh.');
    }

    public function test_companion_queue_page_shows_queue_status_without_patient_details(): void
    {
        $this->openPractice();
        $this->registerPatient(str_repeat('a', 64), 'Nama Pasien Privat')
            ->assertCreated();
        $this->registerPatient(str_repeat('b', 64), 'Nama Pasien Berikutnya')
            ->assertCreated();
        $this->postJson('/api/petugas/queue/call')
            ->assertOk()
            ->assertJsonPath('data.number_label', '01');

        $this->withHeader('X-Device-Token', str_repeat('b', 64))
            ->getJson('/api/companion/queue')
            ->assertOk()
            ->assertJsonPath('entry.number_label', '02')
            ->assertJsonPath('entry.status', 'waiting')
            ->assertJsonPath('ahead', 1)
            ->assertJsonPath('now_serving', '01')
            ->assertDontSee('Nama Pasien Berikutnya');

        $this->get('/antrian')
            ->assertOk()
            ->assertSee('Status antrian Anda')
            ->assertSee('Sedang diperiksa / dipanggil')
            ->assertSee('id="ahead-count"', false)
            ->assertSee('/build/assets/companion-queue-')
            ->assertDontSee('Nama Pasien Privat')
            ->assertDontSee('Nama Pasien Berikutnya');
    }

    public function test_five_patients_can_register_get_called_and_finish_in_queue_order(): void
    {
        $this->openPractice();

        $this->get('/')
            ->assertOk()
            ->assertSee('data-practice-open="true"', false)
            ->assertDontSee('id="registration-fields" class="flex flex-col gap-5" disabled', false);

        $patients = [
            ['name' => 'Pasien Uji Budi Santoso', 'age' => 45, 'address' => 'Jl. Melati No. 12, Bandung', 'complaint' => 'Batuk sejak tiga hari lalu'],
            ['name' => 'Pasien Uji Siti Aminah', 'age' => 32, 'address' => 'Jl. Mawar No. 8, Bandung', 'complaint' => 'Demam dan sakit kepala'],
            ['name' => 'Pasien Uji Ahmad Hidayat', 'age' => 61, 'address' => 'Jl. Kenanga No. 3, Bandung', 'complaint' => 'Kontrol tekanan darah'],
            ['name' => 'Pasien Uji Rina Kurniawati', 'age' => 27, 'address' => 'Jl. Anggrek No. 21, Bandung', 'complaint' => 'Nyeri tenggorokan sejak kemarin'],
            ['name' => 'Pasien Uji Dedi Setiawan', 'age' => 70, 'address' => 'Jl. Cempaka No. 5, Bandung', 'complaint' => 'Kontrol rutin dan pemeriksaan batuk'],
        ];
        $deviceTokens = [];

        foreach ($patients as $index => $patient) {
            $deviceToken = str_repeat(chr(97 + $index), 64);
            $deviceTokens[] = $deviceToken;

            $this->withHeader('X-Device-Token', $deviceToken)
                ->postJson('/api/companion/register', [
                    'patient_name' => $patient['name'],
                    'patient_age' => $patient['age'],
                    'patient_address' => $patient['address'],
                    'complaint' => $patient['complaint'],
                ])
                ->assertCreated()
                ->assertJsonPath('entry.number_label', sprintf('%02d', $index + 1))
                ->assertJsonPath('entry.status', 'waiting')
                ->assertJsonMissingPath('entry.patient');

            $this->withHeader('X-Device-Token', $deviceToken)
                ->getJson('/api/companion/queue')
                ->assertOk()
                ->assertJsonPath('entry.status', 'waiting')
                ->assertJsonMissingPath('entry.patient');
        }

        $entries = QueueEntry::today()->orderBy('number')->get();
        $this->assertCount(5, $entries);

        $this->getJson('/api/petugas/queue')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.status', 'waiting')
            ->assertJsonPath('data.0.patient.name', $patients[0]['name']);

        foreach ($entries as $index => $entry) {
            $this->postJson('/api/petugas/queue/call')
                ->assertOk()
                ->assertJsonPath('data.number_label', sprintf('%02d', $index + 1))
                ->assertJsonPath('data.status', 'called')
                ->assertJsonPath('data.patient.name', $patients[$index]['name']);

            $this->withHeader('X-Device-Token', $deviceTokens[$index])
                ->getJson('/api/companion/queue')
                ->assertOk()
                ->assertJsonPath('entry.status', 'called')
                ->assertJsonMissingPath('entry.patient');

            $this->postJson("/api/petugas/queue/{$entry->id}/done")
                ->assertOk()
                ->assertJsonPath('data.status', 'done');

            $this->withHeader('X-Device-Token', $deviceTokens[$index])
                ->getJson('/api/companion/queue')
                ->assertOk()
                ->assertJsonPath('entry.status', 'done')
                ->assertJsonMissingPath('entry.patient');

            $this->assertDatabaseHas('queue_entries', [
                'id' => $entry->id,
                'status' => 'done',
            ]);
        }

        $this->assertDatabaseCount('queue_entries', 5);
        $this->getJson('/api/petugas/queue?status=done')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_call_sends_a_turn_notification_to_the_registered_device(): void
    {
        $this->openPractice();
        $deviceToken = str_repeat('p', 64);
        $this->registerPatient($deviceToken)->assertCreated();

        $push = \Mockery::mock(PushService::class);
        $push->shouldReceive('sendToDevice')
            ->once()
            ->with(
                hash('sha256', $deviceToken),
                \Mockery::on(fn (array $payload): bool => $payload['type'] === 'your_turn'
                    && $payload['title'] === 'Giliran Anda'
                    && $payload['number'] === '01')
            );
        $this->app->instance(PushService::class, $push);

        $this->postJson('/api/petugas/queue/call')
            ->assertOk()
            ->assertJsonPath('data.status', 'called')
            ->assertJsonPath('data.call_count', 1);
    }

    public function test_numbering_restarts_at_01_on_the_next_jakarta_day(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        Carbon::setTestNow('2026-11-11 23:59:00');
        $this->openPractice();
        $token = $this->registerPatient()->assertCreated()->json('device_token');

        Carbon::setTestNow('2026-11-12 00:01:00');

        $this->registerPatient($token)
            ->assertCreated()
            ->assertJsonPath('entry.number_label', '01');
    }

    public function test_registration_is_rejected_when_all_99_numbers_are_issued(): void
    {
        $this->openPractice();
        $deviceHash = hash('sha256', 'already-registered-device');
        $entries = [];

        for ($number = 1; $number <= 99; $number++) {
            $entries[] = [
                'queue_date' => today()->toDateString(),
                'number' => $number,
                'position' => $number,
                'device_hash' => $deviceHash,
                'patient_name' => 'Pasien '.$number,
                'patient_age' => 60,
                'patient_address' => 'Alamat',
                'complaint' => 'Pemeriksaan',
                'status' => 'waiting',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        QueueEntry::insert($entries);

        $this->registerPatient()
            ->assertConflict()
            ->assertJsonPath('message', 'Antrian hari ini penuh.');

        $this->assertDatabaseCount('queue_entries', 99);
    }

    public function test_companion_responses_do_not_expose_patient_data(): void
    {
        $this->openPractice();

        $registration = $this->registerPatient()
            ->assertCreated()
            ->assertJsonMissingPath('entry.patient')
            ->assertDontSee('Bapak Slamet')
            ->assertDontSee('Jl. Mawar No. 1')
            ->assertDontSee('Batuk dan demam');

        $token = $registration->json('device_token');

        $this->withHeaders(['X-Device-Token' => $token])
            ->getJson('/api/companion/queue')
            ->assertOk()
            ->assertJsonPath('entry.number_label', '01')
            ->assertJsonMissingPath('entry.patient')
            ->assertDontSee('Bapak Slamet')
            ->assertDontSee('Batuk dan demam');
    }

    public function test_queue_actions_are_logged_with_event_history(): void
    {
        $this->openPractice();
        $entryId = QueueEntry::first()?->id;

        if ($entryId === null) {
            $token = $this->registerPatient()->assertCreated()->json('device_token');
            $entryId = QueueEntry::firstOrFail()->id;
        }

        $this->postJson('/api/petugas/queue/call')->assertOk();
        $this->postJson("/api/petugas/queue/{$entryId}/skip")->assertOk();

        $this->assertDatabaseHas('queue_events', [
            'queue_entry_id' => $entryId,
            'event_type' => 'registered',
        ]);
        $this->assertDatabaseHas('queue_events', [
            'queue_entry_id' => $entryId,
            'event_type' => 'called',
            'actor_role' => 'petugas',
        ]);
        $this->assertDatabaseHas('queue_events', [
            'queue_entry_id' => $entryId,
            'event_type' => 'skipped',
            'actor_role' => 'petugas',
        ]);
    }

    public function test_same_device_cannot_register_a_second_active_queue_until_the_first_is_resolved(): void
    {
        $this->openPractice();

        $token = $this->registerPatient()->assertCreated()->json('device_token');

        $this->registerPatient($token, 'Pasien Kedua dari Perangkat Sama')
            ->assertStatus(409)
            ->assertJsonPath('message', 'Perangkat ini sudah memiliki antrian aktif. Selesaikan atau batalkan antrian sebelumnya sebelum mendaftar baru.');

        $this->assertDatabaseCount('queue_entries', 1);

        $first = QueueEntry::firstOrFail();
        $this->postJson("/api/petugas/queue/{$first->id}/cancel")->assertOk();

        $this->registerPatient($token, 'Pasien Kedua dari Perangkat Sama')
            ->assertCreated()
            ->assertJsonPath('entry.number_label', '02');

        $this->assertDatabaseCount('queue_entries', 2);
    }

    public function test_call_limited_to_three_then_skip_moves_patient_down(): void
    {
        $this->openPractice();
        foreach (['A', 'B', 'C', 'D'] as $name) {
            $this->registerPatient(null, $name)->assertCreated();
        }

        $this->postJson('/api/petugas/queue/call')->assertOk()->assertJsonPath('data.call_count', 1);
        $this->postJson('/api/petugas/queue/call')->assertOk()->assertJsonPath('data.call_count', 2);
        $this->postJson('/api/petugas/queue/call')->assertOk()->assertJsonPath('data.call_count', 3);
        $this->postJson('/api/petugas/queue/call')->assertStatus(409);

        $first = QueueEntry::where('number', 1)->first();
        $this->postJson("/api/petugas/queue/{$first->id}/skip")->assertOk();

        $order = $this->getJson('/api/petugas/queue?status=waiting')->json('data.*.number_label');
        $this->assertSame(['02', '03', '04', '01'], $order);
    }

    public function test_auto_skip_after_three_calls_and_two_minutes(): void
    {
        $this->openPractice();
        $this->registerPatient(null, 'A')->assertCreated();
        $this->registerPatient(null, 'B')->assertCreated();

        $this->postJson('/api/petugas/queue/call')->assertOk();
        $this->postJson('/api/petugas/queue/call')->assertOk();
        $this->postJson('/api/petugas/queue/call')->assertOk();

        Carbon::setTestNow(now()->addMinute());
        $this->artisan('queue:process-timeouts')->assertSuccessful();
        $this->assertSame('called', QueueEntry::where('number', 1)->first()->status->value);

        Carbon::setTestNow(now()->addMinutes(2));
        $this->artisan('queue:process-timeouts')->assertSuccessful();

        $order = $this->getJson('/api/petugas/queue?status=waiting')->json('data.*.number_label');
        $this->assertSame(['02', '01'], $order);
    }

    public function test_cancel_frees_device_to_register_again(): void
    {
        $this->openPractice();

        $token = $this->registerPatient()->assertCreated()->json('device_token');
        $id = QueueEntry::first()->id;

        $this->postJson("/api/petugas/queue/{$id}/cancel")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->registerPatient($token)->assertCreated();
    }

    public function test_skipped_patient_remains_visible_and_device_cannot_register_another_active_queue(): void
    {
        $this->openPractice();
        $token = $this->registerPatient()->assertCreated()->json('device_token');
        $id = QueueEntry::firstOrFail()->id;

        $this->postJson('/api/petugas/queue/call')->assertOk();
        $this->postJson("/api/petugas/queue/{$id}/skip")
            ->assertOk()
            ->assertJsonPath('data.status', 'waiting');

        $this->withHeader('X-Device-Token', $token)
            ->getJson('/api/companion/queue')
            ->assertOk()
            ->assertJsonPath('entry.number_label', '01')
            ->assertJsonPath('entry.status', 'waiting');

        $this->registerPatient($token, 'Pasien Setelah Skip')
            ->assertStatus(409)
            ->assertJsonPath('message', 'Perangkat ini sudah memiliki antrian aktif. Selesaikan atau batalkan antrian sebelumnya sebelum mendaftar baru.');
    }

    public function test_staff_can_move_a_waiting_patient_down_without_changing_their_queue_number(): void
    {
        $this->openPractice();
        $this->registerPatient(null, 'Pasien satu')->assertCreated();
        $this->registerPatient(null, 'Pasien dua')->assertCreated();
        $this->registerPatient(null, 'Pasien tiga')->assertCreated();
        $first = QueueEntry::where('number', 1)->firstOrFail();

        $this->postJson("/api/petugas/queue/{$first->id}/move", ['steps' => 2])
            ->assertOk()
            ->assertJsonPath('data.number_label', '01')
            ->assertJsonPath('data.position', 3);

        $this->assertSame(
            ['02', '03', '01'],
            $this->getJson('/api/petugas/queue?status=waiting')->json('data.*.number_label')
        );
    }

    public function test_practice_can_be_opened_and_patients_can_register_on_weekends(): void
    {
        $this->actAsPetugas();

        foreach (['2026-11-14 10:00:00', '2026-11-15 10:00:00'] as $weekendDate) {
            $this->travelTo($weekendDate);

            $this->postJson('/api/petugas/practice/open')
                ->assertOk()
                ->assertJsonPath('practice.state', 'buka')
                ->assertJsonPath('practice.is_open', true);

            $this->assertDatabaseHas('settings', ['key' => 'is_open', 'value' => '1']);

            $this->get('/')
                ->assertOk()
                ->assertSee('data-practice-open="true"', false);

            $this->registerPatient()->assertCreated();
        }
    }

    public function test_companion_cannot_access_petugas_endpoints(): void
    {
        $this->getJson('/api/petugas/queue')->assertUnauthorized();
        $this->withHeaders(['X-Device-Token' => str_repeat('a', 64)])
            ->getJson('/api/petugas/queue')->assertUnauthorized();
    }
}

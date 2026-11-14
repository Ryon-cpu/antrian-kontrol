<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Exceptions\ApiException;
use App\Models\PushSubscription;
use App\Models\QueueEntry;
use App\Models\QueueEvent;
use App\Models\QueueNumberCounter;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class QueueService
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly PushService $push,
    ) {}

    // ------------------------------------------------------------------
    // Jadwal praktek
    // ------------------------------------------------------------------

    /** @return array{state: string, is_open: bool, message: string} */
    public function practiceStatus(): array
    {
        if ($this->settings->bool('is_open')) {
            return ['state' => 'buka', 'is_open' => true, 'message' => 'Praktek sedang buka.'];
        }

        return ['state' => 'tutup', 'is_open' => false, 'message' => 'Antrian tutup. Pendaftaran belum dibuka atau sudah selesai.'];
    }

    public function openPractice(): void
    {
        $this->settings->set('is_open', '1');
        $this->settings->set('opened_at', now()->toDateTimeString());
    }

    public function closePractice(): void
    {
        $this->settings->set('is_open', '0');
        $this->settings->set('closed_at', now()->toDateTimeString());
    }

    // ------------------------------------------------------------------
    // Pendaftaran oleh pendamping
    // ------------------------------------------------------------------

    /**
     * @param  array{patient_name: string, patient_age: int, patient_address: string, complaint: string}  $data
     * @return array{0: QueueEntry, 1: string} [antrian, token perangkat]
     */
    public function register(array $data, ?string $deviceToken): array
    {
        $status = $this->practiceStatus();
        if ($status['state'] !== 'buka') {
            throw new ApiException($status['message'], 423);
        }

        $deviceToken = $deviceToken ?: Str::random(64);
        $hash = hash('sha256', $deviceToken);

        $lock = Cache::lock("queue:device:{$hash}", 10);
        $entry = null;

        for ($attempt = 0; $attempt < 3 && $entry === null; $attempt++) {
            try {
                $entry = $lock->block(5, function () use ($data, $hash) {
                    return DB::transaction(function () use ($data, $hash) {
                        $existing = QueueEntry::today()
                            ->where('device_hash', $hash)
                            ->whereIn('status', QueueStatus::activeValues())
                            ->lockForUpdate()
                            ->first();

                        if ($existing) {
                            throw new ApiException(
                                'Perangkat ini sudah memiliki antrian aktif. Selesaikan atau batalkan antrian sebelumnya sebelum mendaftar baru.',
                                409
                            );
                        }

                        $queueDate = today()->toDateString();
                        $counter = QueueNumberCounter::query()
                            ->where('queue_date', $queueDate)
                            ->lockForUpdate()
                            ->first();

                        if (! $counter) {
                            $counter = QueueNumberCounter::create([
                                'queue_date' => $queueDate,
                                'last_number' => (int) QueueEntry::query()
                                    ->where('queue_date', $queueDate)
                                    ->max('number'),
                            ]);
                        }

                        $max = config('antrian.max_number');
                        $next = $counter->last_number + 1;

                        if ($next > $max) {
                            throw new ApiException('Antrian hari ini penuh.', 409);
                        }

                        $counter->forceFill(['last_number' => $next])->save();

                        $entry = QueueEntry::create([
                            'queue_date' => $queueDate,
                            'number' => $next,
                            'position' => (int) QueueEntry::today()->max('position') + 1,
                            'device_hash' => $hash,
                            'patient_name' => $data['patient_name'],
                            'patient_age' => $data['patient_age'],
                            'patient_address' => $data['patient_address'],
                            'complaint' => $data['complaint'],
                            'status' => QueueStatus::Waiting,
                        ]);

                        $this->recordEvent($entry, 'registered', metadata: ['device_hash' => $hash]);

                        return $entry;
                    });
                });
            } catch (UniqueConstraintViolationException) {
                if ($attempt === 2) {
                    throw new ApiException('Server sedang sibuk, silakan coba lagi.', 503);
                }
            }
        }

        if ($entry === null || $entry === false) {
            throw new ApiException('Server sedang sibuk, silakan coba lagi.', 503);
        }

        return [$entry, $deviceToken];
    }

    // ------------------------------------------------------------------
    // Aksi petugas
    // ------------------------------------------------------------------

    /** Panggil pasien. Tanpa $entryId: panggil ulang yang sedang dipanggil, atau yang berikutnya. */
    public function call(?int $entryId = null, ?User $actor = null): QueueEntry
    {
        $maxCalls = config('antrian.max_calls');

        $entry = DB::transaction(function () use ($entryId, $maxCalls, $actor) {
            $current = QueueEntry::today()->where('status', QueueStatus::Called->value)->lockForUpdate()->first();

            if ($entryId !== null) {
                $entry = $this->findToday($entryId, lock: true);
                if ($current && $current->id !== $entry->id) {
                    throw new ApiException(
                        "Masih ada pasien yang sedang dipanggil (nomor {$current->number_label}). Selesaikan, lewati, atau batalkan dahulu.",
                        409
                    );
                }
            } else {
                $entry = $current ?? QueueEntry::today()
                    ->where('status', QueueStatus::Waiting->value)
                    ->orderBy('position')
                    ->lockForUpdate()
                    ->first();

                if (! $entry) {
                    throw new ApiException('Tidak ada antrian yang menunggu.', 404);
                }
            }

            if (! in_array($entry->status, [QueueStatus::Waiting, QueueStatus::Called], true)) {
                throw new ApiException('Antrian ini sudah selesai atau dibatalkan.', 409);
            }

            if ($entry->call_count >= $maxCalls) {
                throw new ApiException(
                    "Pasien sudah dipanggil {$maxCalls} kali. Gunakan Lewati atau Batalkan.",
                    409
                );
            }

            $entry->forceFill([
                'status' => QueueStatus::Called,
                'call_count' => $entry->call_count + 1,
                'last_called_at' => now(),
                'called_at' => $entry->called_at ?? now(),
                'notified_near' => true,
            ])->save();

            $this->recordEvent($entry, 'called', $actor, ['call_count' => $entry->call_count]);

            return $entry;
        });

        if ($entry->call_count === 1) {
            $this->notify($entry, 'your_turn', 'Giliran Anda', "Sekarang giliran anda (nomor {$entry->number_label}).", sticky: true);
        } else {
            $this->notify($entry, 'recall', 'Anda dipanggil kembali', "Nomor {$entry->number_label} dipanggil untuk ke-{$entry->call_count} kali. Mohon segera ke ruang periksa.", sticky: true);
        }

        $this->notifyNear();

        return $entry->refresh();
    }

    public function done(int $entryId, ?User $actor = null): QueueEntry
    {
        $entry = DB::transaction(function () use ($entryId, $actor) {
            $entry = $this->findToday($entryId, lock: true);

            if ($entry->status !== QueueStatus::Called) {
                throw new ApiException('Hanya pasien yang sedang dipanggil yang bisa diselesaikan.', 409);
            }

            $entry->forceFill(['status' => QueueStatus::Done, 'finished_at' => now()])->save();
            $this->recordEvent($entry, 'done', $actor);

            return $entry;
        });

        $this->notify($entry, 'done', 'Terima kasih', 'Terima kasih karena sudah berkunjung, semoga pasien cepat sembuh.');
        $this->notifyNear();

        return $entry;
    }

    /** "Lewati": pasien tidak hadir / belum siap, digeser agak ke bawah. */
    public function skip(int $entryId, bool $auto = false, ?User $actor = null): QueueEntry
    {
        $entry = DB::transaction(function () use ($entryId, $auto, $actor) {
            $entry = $this->findToday($entryId, lock: true);

            if (! in_array($entry->status, [QueueStatus::Waiting, QueueStatus::Called], true)) {
                throw new ApiException('Antrian ini sudah selesai atau dibatalkan.', 409);
            }

            $this->shiftDown($entry, config('antrian.shift_steps'));
            $this->recordEvent($entry->fresh(), 'skipped', $actor, ['auto' => $auto]);

            return $entry;
        });

        $this->notify(
            $entry,
            'skipped',
            'Antrian digeser',
            $auto
                ? 'Anda belum hadir saat dipanggil, antrian digeser ke bawah. Mohon segera ke ruang periksa.'
                : 'Antrian anda dilewati dan digeser ke bawah. Mohon tetap bersiap.',
            sticky: true
        );
        $this->notifyNear();

        return $entry->refresh();
    }

    /** Geser nomor ke bawah (mis. pasien pulang dulu karena antrian masih lama). */
    public function move(int $entryId, int $steps, ?User $actor = null): QueueEntry
    {
        $entry = DB::transaction(function () use ($entryId, $steps, $actor) {
            $entry = $this->findToday($entryId, lock: true);

            if ($entry->status !== QueueStatus::Waiting) {
                throw new ApiException('Hanya antrian berstatus menunggu yang bisa digeser.', 409);
            }

            $this->shiftDown($entry, $steps);
            $this->recordEvent($entry->fresh(), 'moved', $actor, ['steps' => $steps]);

            return $entry;
        });

        $this->notify($entry, 'moved', 'Antrian digeser', "Nomor {$entry->number_label} digeser ke urutan lebih bawah.");
        $this->notifyNear();

        return $entry->refresh();
    }

    public function cancel(int $entryId, ?User $actor = null): QueueEntry
    {
        $entry = DB::transaction(function () use ($entryId, $actor) {
            $entry = $this->findToday($entryId, lock: true);

            if (! in_array($entry->status, [QueueStatus::Waiting, QueueStatus::Called], true)) {
                throw new ApiException('Antrian ini sudah selesai atau dibatalkan.', 409);
            }

            $entry->forceFill(['status' => QueueStatus::Cancelled, 'finished_at' => now()])->save();
            $this->recordEvent($entry, 'cancelled', $actor);

            return $entry;
        });

        $this->notify($entry, 'cancelled', 'Antrian dibatalkan', "Antrian nomor {$entry->number_label} dibatalkan.");
        $this->notifyNear();

        return $entry;
    }

    // ------------------------------------------------------------------
    // Otomatis (dijalankan scheduler tiap menit)
    // ------------------------------------------------------------------

    /** Pasien yang sudah dipanggil N kali + jeda M menit tanpa hadir → digeser otomatis. */
    public function autoSkipExpired(): int
    {
        $ids = QueueEntry::today()
            ->where('status', QueueStatus::Called->value)
            ->where('call_count', '>=', config('antrian.max_calls'))
            ->where('last_called_at', '<=', now()->subMinutes(config('antrian.recall_wait_minutes')))
            ->pluck('id');

        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->skip($id, auto: true);
                $count++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $count;
    }

    /** Kirim peringatan "tinggal N antrian lagi" satu kali per pendamping. */
    public function notifyNear(): void
    {
        $threshold = config('antrian.near_threshold');
        $calledCount = QueueEntry::today()->where('status', QueueStatus::Called->value)->count();

        $waiting = QueueEntry::today()
            ->where('status', QueueStatus::Waiting->value)
            ->orderBy('position')
            ->get()
            ->values();

        foreach ($waiting as $index => $entry) {
            $ahead = $index + $calledCount;
            if ($ahead > $threshold) {
                break;
            }
            if ($entry->notified_near) {
                continue;
            }

            $entry->forceFill(['notified_near' => true])->save();

            $body = $ahead === 0
                ? "Anda berikutnya (nomor {$entry->number_label}), mohon bersiap."
                : "Tinggal {$ahead} antrian lagi sebelum nomor {$entry->number_label}, mohon bersiap.";

            $this->notify($entry, 'near', 'Giliran hampir tiba', $body);
        }
    }

    // ------------------------------------------------------------------
    // Util
    // ------------------------------------------------------------------

    public function nowServing(): ?QueueEntry
    {
        return QueueEntry::today()
            ->where('status', QueueStatus::Called->value)
            ->orderByDesc('last_called_at')
            ->first();
    }

    /** Jumlah antrian di depan $entry (yang menunggu + yang sedang dipanggil). */
    public function aheadOf(QueueEntry $entry): int
    {
        if (! in_array($entry->status, [QueueStatus::Waiting, QueueStatus::Called], true)) {
            return 0;
        }

        if ($entry->status === QueueStatus::Called) {
            return 0;
        }

        $waitingAhead = QueueEntry::today()
            ->where('status', QueueStatus::Waiting->value)
            ->where('position', '<', $entry->position)
            ->count();

        $called = QueueEntry::today()->where('status', QueueStatus::Called->value)->count();

        return $waitingAhead + $called;
    }

    private function findToday(int $id, bool $lock = false): QueueEntry
    {
        $query = QueueEntry::today()->whereKey($id);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    /** Pindahkan $entry $steps urutan ke bawah di antara antrian yang menunggu. */
    private function shiftDown(QueueEntry $entry, int $steps): void
    {
        $entry->forceFill([
            'status' => QueueStatus::Waiting,
            'call_count' => 0,
            'called_at' => null,
            'last_called_at' => null,
            'notified_near' => false,
        ])->save();

        $ids = QueueEntry::today()
            ->where('status', QueueStatus::Waiting->value)
            ->orderBy('position')
            ->lockForUpdate()
            ->pluck('id')
            ->all();

        $index = array_search($entry->id, $ids, true);
        array_splice($ids, $index, 1);
        array_splice($ids, min($index + $steps, count($ids)), 0, [$entry->id]);

        foreach ($ids as $i => $id) {
            QueueEntry::whereKey($id)->update(['position' => $i + 1]);
        }
    }

    private function notify(QueueEntry $entry, string $type, string $title, string $body, bool $sticky = false): void
    {
        $this->push->sendToDevice($entry->device_hash, [
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'number' => $entry->number_label,
            'tag' => "antrian-{$entry->id}-{$type}",
            'requireInteraction' => $sticky,
            'vibrate' => $sticky ? [300, 150, 300, 150, 600] : [200],
            'url' => '/',
        ]);
    }

    private function recordEvent(QueueEntry $entry, string $eventType, ?User $actor = null, array $metadata = []): void
    {
        $payload = [
            'queue_entry_id' => $entry->id,
            'event_type' => $eventType,
            'metadata' => $metadata ?: null,
        ];

        if ($actor) {
            $payload['user_id'] = $actor->id;
            $payload['actor_role'] = $actor->role instanceof \BackedEnum ? $actor->role->value : (string) $actor->role;
        }

        QueueEvent::create($payload);
    }

    /** Hapus data lama sesuai masa simpan. @return int jumlah antrian terhapus */
    public function purgeOld(): int
    {
        $cutoff = today()->subDays(config('antrian.retention_days'))->toDateString();

        $deleted = QueueEntry::where('queue_date', '<', $cutoff)->delete();

        PushSubscription::whereNotIn('device_hash', QueueEntry::select('device_hash'))->delete();

        return $deleted;
    }
}

<?php

namespace App\Http\Controllers\Companion;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterPatientRequest;
use App\Http\Resources\CompanionEntryResource;
use App\Models\PushSubscription;
use App\Models\QueueEntry;
use App\Services\QueueService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompanionController extends Controller
{
    public function __construct(private readonly QueueService $queue) {}

    public function page(SettingService $settings): View
    {
        return view('companion.queue', [
            'appName' => $settings->get('app_name', 'Antrian Pasien'),
        ]);
    }

    /** Daftarkan pasien (tanpa login/OTP). Menghasilkan device_token untuk disimpan di HP. */
    public function register(RegisterPatientRequest $request): JsonResponse
    {
        $token = (string) $request->header('X-Device-Token');
        $token = strlen($token) === 64 ? $token : null;

        [$entry, $deviceToken] = $this->queue->register($request->validated(), $token);

        return response()->json([
            'device_token' => $deviceToken,
            'entry' => new CompanionEntryResource($entry),
            'ahead' => $this->queue->aheadOf($entry),
            'practice' => $this->queue->practiceStatus(),
        ], 201);
    }

    /** Status antrian milik perangkat ini (hari ini). */
    public function status(Request $request): JsonResponse
    {
        $entry = QueueEntry::today()
            ->where('device_hash', $request->attributes->get('device_hash'))
            ->latest('id')
            ->first();

        if (! $entry) {
            throw new ApiException('Belum ada antrian hari ini.', 404);
        }

        return response()->json([
            'entry' => new CompanionEntryResource($entry),
            'ahead' => $this->queue->aheadOf($entry),
            'now_serving' => $this->queue->nowServing()?->number_label,
            'practice' => $this->queue->practiceStatus(),
        ]);
    }

    /** Simpan langganan Web Push dari service worker di HP pendamping. */
    public function subscribePush(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000', 'starts_with:https://'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);
        $parsedEndpoint = parse_url($data['endpoint']);
        $host = is_array($parsedEndpoint) ? strtolower($parsedEndpoint['host'] ?? '') : '';
        $port = is_array($parsedEndpoint) ? ($parsedEndpoint['port'] ?? null) : null;
        $allowedHosts = [
            'fcm.googleapis.com',
            'web.push.apple.com',
        ];
        $allowedHostSuffixes = [
            '.push.services.mozilla.com',
            '.notify.windows.com',
            '.push.apple.com',
        ];
        $hostIsAllowed = ($port === null || $port === 443) && (
            in_array($host, $allowedHosts, true)
            || collect($allowedHostSuffixes)->contains(fn (string $suffix) => str_ends_with($host, $suffix))
        );

        if (! $hostIsAllowed) {
            throw ValidationException::withMessages([
                'endpoint' => 'Endpoint push harus berasal dari layanan push browser yang didukung.',
            ]);
        }

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'device_hash' => $request->attributes->get('device_hash'),
                'endpoint' => $data['endpoint'],
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
            ]
        );

        return response()->json(['message' => 'Notifikasi diaktifkan.'], 201);
    }
}

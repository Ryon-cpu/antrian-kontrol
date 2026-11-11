<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\QueueEntry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pendamping tidak login. Perangkatnya dikenali lewat header X-Device-Token
 * yang diberikan server saat pendaftaran pertama (disimpan di HP pendamping).
 */
class EnsureCompanionDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->header('X-Device-Token');

        if (strlen($token) !== 64) {
            throw new ApiException('Perangkat tidak dikenali. Silakan daftar melalui barcode.', 401);
        }

        $hash = hash('sha256', $token);

        if (! QueueEntry::where('device_hash', $hash)->exists()) {
            throw new ApiException('Perangkat tidak dikenali. Silakan daftar melalui barcode.', 401);
        }

        $request->attributes->set('device_hash', $hash);

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QueueEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** Ringkasan per hari. Data hanya tersedia sesuai masa simpan (default 30 hari). */
    public function daily(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = $data['from'] ?? today()->subDays(29)->toDateString();
        $to = $data['to'] ?? today()->toDateString();

        $rows = QueueEntry::query()
            ->whereBetween('queue_date', [$from, $to])
            ->groupBy('queue_date')
            ->orderByDesc('queue_date')
            ->get([
                'queue_date',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done"),
                DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN status IN ('waiting','called') THEN 1 ELSE 0 END) as unfinished"),
            ]);

        return response()->json([
            'from' => $from,
            'to' => $to,
            'data' => $rows->map(fn ($r) => [
                'date' => $r->queue_date->toDateString(),
                'total' => (int) $r->total,
                'done' => (int) $r->done,
                'cancelled' => (int) $r->cancelled,
                'unfinished' => (int) $r->unfinished,
            ]),
        ]);
    }
}

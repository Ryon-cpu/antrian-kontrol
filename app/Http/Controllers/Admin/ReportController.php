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
                DB::raw("SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting"),
                DB::raw("SUM(CASE WHEN status = 'called' THEN 1 ELSE 0 END) as called"),
                DB::raw("SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done"),
                DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN status IN ('waiting','called') THEN 1 ELSE 0 END) as unfinished"),
            ]);

        $skippedByDate = DB::table('queue_events')
            ->join('queue_entries', 'queue_entries.id', '=', 'queue_events.queue_entry_id')
            ->where('queue_events.event_type', 'skipped')
            ->whereBetween('queue_entries.queue_date', [$from, $to])
            ->groupBy('queue_entries.queue_date')
            ->get([
                'queue_entries.queue_date',
                DB::raw('COUNT(DISTINCT queue_events.queue_entry_id) as skipped'),
            ])
            ->keyBy('queue_date');

        return response()->json([
            'from' => $from,
            'to' => $to,
            'data' => $rows->map(fn ($r) => [
                'date' => $r->queue_date->toDateString(),
                'total' => (int) $r->total,
                'waiting' => (int) $r->waiting,
                'called' => (int) $r->called,
                'done' => (int) $r->done,
                'cancelled' => (int) $r->cancelled,
                'unfinished' => (int) $r->unfinished,
                'skipped' => (int) ($skippedByDate[$r->queue_date->toDateString()]->skipped ?? 0),
            ]),
        ]);
    }
}

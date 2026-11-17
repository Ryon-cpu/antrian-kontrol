<?php

namespace App\Http\Controllers\Petugas;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\QueueEntryResource;
use App\Models\QueueEntry;
use App\Services\QueueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QueueController extends Controller
{
    public function __construct(private readonly QueueService $queue) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate(['status' => ['nullable', Rule::enum(QueueStatus::class)]]);

        $entries = QueueEntry::today()
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByRaw("CASE status WHEN 'called' THEN 0 WHEN 'waiting' THEN 1 WHEN 'done' THEN 2 ELSE 3 END")
            ->orderBy('position')
            ->get();

        return response()->json([
            'practice' => $this->queue->practiceStatus(),
            'now_serving' => $this->queue->nowServing()?->number_label,
            'data' => QueueEntryResource::collection($entries),
        ]);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $today = today()->toDateString();
        $earliestDate = today()->subDays(29)->toDateString();
        $filters = Validator::make([
            'from' => $request->query('from', $earliestDate),
            'to' => $request->query('to', $today),
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'per_page' => $request->query('per_page', 20),
            'page' => $request->query('page', 1),
        ], [
            'from' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$earliestDate, 'before_or_equal:'.$today],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:'.$today],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(QueueStatus::class)],
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'page' => ['required', 'integer', 'min:1'],
        ], [
            'from.after_or_equal' => 'Riwayat hanya tersedia untuk 30 hari terakhir.',
            'from.before_or_equal' => 'Tanggal awal tidak boleh melewati hari ini.',
            'to.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
            'to.before_or_equal' => 'Tanggal akhir tidak boleh melewati hari ini.',
            'per_page.max' => 'Jumlah data per halaman maksimal 100.',
        ])->validate();

        $entries = QueueEntry::query()
            ->whereBetween('queue_date', [$filters['from'], $filters['to']])
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $patientQuery) use ($search): void {
                    $patientQuery
                        ->where('patient_name', 'like', '%'.$search.'%')
                        ->orWhere('patient_address', 'like', '%'.$search.'%')
                        ->orWhere('complaint', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('queue_date')
            ->orderBy('number')
            ->paginate($filters['per_page'], ['*'], 'page', $filters['page'])
            ->appends([
                'from' => $filters['from'],
                'to' => $filters['to'],
                'search' => $filters['search'],
                'status' => $filters['status'],
                'per_page' => $filters['per_page'],
            ]);

        return QueueEntryResource::collection($entries)
            ->additional([
                'filters' => [
                    'from' => $filters['from'],
                    'to' => $filters['to'],
                    'available_from' => $earliestDate,
                    'available_to' => $today,
                    'search' => $filters['search'] ?? '',
                    'status' => $filters['status'] ?? 'all',
                ],
            ]);
    }

    /** Body opsional: entry_id. Tanpa entry_id = panggil ulang / panggil berikutnya. */
    public function call(Request $request): JsonResponse
    {
        $data = $request->validate(['entry_id' => ['nullable', 'integer']]);

        return $this->respond($this->queue->call($data['entry_id'] ?? null, $request->user()));
    }

    public function done(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->queue->done($id, $request->user()));
    }

    public function skip(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->queue->skip($id, auto: false, actor: $request->user()));
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        return $this->respond($this->queue->cancel($id, $request->user()));
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['steps' => ['nullable', 'integer', 'min:1', 'max:20']]);

        return $this->respond($this->queue->move($id, $data['steps'] ?? config('antrian.shift_steps'), $request->user()));
    }

    public function destroy(int $id): JsonResponse
    {
        $entry = QueueEntry::today()->where('id', $id)->whereIn('status', ['cancelled', 'done'])->firstOrFail();
        $entry->delete();

        return response()->json(['message' => 'Data antrian berhasil dihapus.']);
    }

    private function respond(QueueEntry $entry): JsonResponse
    {
        return response()->json(['data' => new QueueEntryResource($entry)]);
    }
}

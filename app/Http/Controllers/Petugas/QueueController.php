<?php

namespace App\Http\Controllers\Petugas;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\QueueEntryResource;
use App\Models\QueueEntry;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    /** Body opsional: entry_id. Tanpa entry_id = panggil ulang / panggil berikutnya. */
    public function call(Request $request): JsonResponse
    {
        $data = $request->validate(['entry_id' => ['nullable', 'integer']]);

        return $this->respond($this->queue->call($data['entry_id'] ?? null));
    }

    public function done(int $id): JsonResponse
    {
        return $this->respond($this->queue->done($id));
    }

    public function skip(int $id): JsonResponse
    {
        return $this->respond($this->queue->skip($id));
    }

    public function cancel(int $id): JsonResponse
    {
        return $this->respond($this->queue->cancel($id));
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['steps' => ['nullable', 'integer', 'min:1', 'max:20']]);

        return $this->respond($this->queue->move($id, $data['steps'] ?? config('antrian.shift_steps')));
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

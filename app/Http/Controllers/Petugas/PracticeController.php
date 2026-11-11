<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;

class PracticeController extends Controller
{
    public function __construct(private readonly QueueService $queue) {}

    public function show(): JsonResponse
    {
        return response()->json(['practice' => $this->queue->practiceStatus()]);
    }

    public function open(): JsonResponse
    {
        $this->queue->openPractice();

        return response()->json(['practice' => $this->queue->practiceStatus()]);
    }

    public function close(): JsonResponse
    {
        $this->queue->closePractice();

        return response()->json(['practice' => $this->queue->practiceStatus()]);
    }
}

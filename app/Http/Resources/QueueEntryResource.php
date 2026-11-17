<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tampilan lengkap untuk petugas/admin (termasuk data pasien). */
class QueueEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'queue_date' => $this->queue_date?->toDateString(),
            'number' => $this->number,
            'number_label' => $this->number_label,
            'position' => $this->position,
            'status' => $this->status->value,
            'call_count' => $this->call_count,
            'last_called_at' => $this->last_called_at?->toIso8601String(),
            'registered_at' => $this->created_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'patient' => [
                'name' => $this->patient_name,
                'age' => $this->patient_age,
                'address' => $this->patient_address,
                'complaint' => $this->complaint,
            ],
        ];
    }
}

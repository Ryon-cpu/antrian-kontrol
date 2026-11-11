<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tampilan untuk pendamping: hanya antrian miliknya sendiri. */
class CompanionEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'number_label' => $this->number_label,
            'status' => $this->status->value,
            'call_count' => $this->call_count,
        ];
    }
}

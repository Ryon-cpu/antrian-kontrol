<?php

namespace App\Models;

use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class QueueEntry extends Model
{
    protected $fillable = [
        'queue_date', 'number', 'position', 'device_hash',
        'patient_name', 'patient_age', 'patient_address', 'complaint',
        'status', 'call_count', 'last_called_at', 'called_at', 'finished_at', 'notified_near',
    ];

    protected function casts(): array
    {
        return [
            'queue_date' => 'date:Y-m-d',
            'status' => QueueStatus::class,
            'number' => 'integer',
            'position' => 'integer',
            'call_count' => 'integer',
            'patient_age' => 'integer',
            'last_called_at' => 'datetime',
            'called_at' => 'datetime',
            'finished_at' => 'datetime',
            'notified_near' => 'boolean',
        ];
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->where('queue_date', today()->toDateString());
    }

    protected function numberLabel(): Attribute
    {
        return Attribute::get(fn () => str_pad((string) $this->number, 2, '0', STR_PAD_LEFT));
    }
}

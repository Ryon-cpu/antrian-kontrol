<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueNumberCounter extends Model
{
    protected $fillable = ['queue_date', 'last_number'];

    protected function casts(): array
    {
        return [
            'queue_date' => 'date:Y-m-d',
            'last_number' => 'integer',
        ];
    }
}

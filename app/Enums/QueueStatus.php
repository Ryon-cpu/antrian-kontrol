<?php

namespace App\Enums;

enum QueueStatus: string
{
    case Waiting = 'waiting';
    case Called = 'called';
    case Done = 'done';
    case Cancelled = 'cancelled';

    /** @return list<string> status yang masih "aktif" (belum selesai). */
    public static function activeValues(): array
    {
        return [self::Waiting->value, self::Called->value];
    }
}

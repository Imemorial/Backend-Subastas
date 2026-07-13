<?php

declare(strict_types=1);

namespace App\Enums;

enum AuctionStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Paused = 'paused';
    case Ended = 'ended';
    case Cancelled = 'cancelled';

    public function isBiddable(): bool
    {
        return $this === self::Active;
    }
}

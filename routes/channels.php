<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('auction.{auctionId}', function ($user, int $auctionId) {
    return $user !== null;
});

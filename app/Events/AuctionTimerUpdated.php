<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Auction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AuctionTimerUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Auction $auction,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('auction.'.$this->auction->id);
    }

    public function broadcastAs(): string
    {
        return 'timer.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->auction->id,
            'remaining_seconds' => $this->auction->remaining_seconds,
            'ends_at' => $this->auction->ends_at?->toIso8601String(),
            'closure_allowed' => $this->auction->closure_allowed,
        ];
    }
}

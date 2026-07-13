<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Bid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AuctionBidPlaced implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Bid $bid,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('auction.'.$this->bid->auction_id);
    }

    public function broadcastAs(): string
    {
        return 'bid.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'auction_id' => $this->bid->auction_id,
            'bid_id' => $this->bid->id,
            'amount' => (float) $this->bid->amount,
            'bits_spent' => (int) $this->bid->bits_spent,
            'user' => [
                'id' => $this->bid->user->id,
                'name' => $this->bid->user->name,
            ],
            'placed_at' => $this->bid->bid_at?->toIso8601String(),
        ];
    }
}

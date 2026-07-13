<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Auction */
final class AuctionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastBid = $this->relationLoaded('bids')
            ? $this->bids->first()
            : null;

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'starting_price' => (float) $this->starting_price,
            'current_price' => (float) $this->current_price,
            'bid_increment' => (float) $this->bid_increment,
            'remaining_seconds' => $this->remaining_seconds,
            'total_bids' => $this->total_bids,
            'bits_consumed' => $this->bits_consumed,
            'closure_allowed' => $this->closure_allowed,
            'min_margin_percent' => (float) $this->min_margin_percent,
            'max_margin_percent' => (float) $this->max_margin_percent,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'product' => new ProductResource($this->whenLoaded('product')),
            'last_bidder' => $lastBid ? [
                'id' => $lastBid->user_id,
                'name' => $lastBid->user?->name,
            ] : null,
            'winner' => $this->whenLoaded('winner', fn () => $this->winner ? [
                'id' => $this->winner->id,
                'name' => $this->winner->name,
            ] : null),
            'recent_bids' => BidResource::collection($this->whenLoaded('bids')),
        ];
    }
}

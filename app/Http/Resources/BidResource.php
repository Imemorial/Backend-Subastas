<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Bid */
final class BidResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'bits_spent' => $this->bits_spent,
            'is_winning' => $this->is_winning,
            'margin_percent_at_bid' => $this->margin_percent_at_bid !== null
                ? (float) $this->margin_percent_at_bid
                : null,
            'bid_at' => $this->bid_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
        ];
    }
}

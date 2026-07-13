<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    protected $fillable = [
        'auction_id',
        'user_id',
        'amount',
        'bits_spent',
        'is_winning',
        'margin_percent_at_bid',
        'closure_was_allowed',
        'bid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_winning' => 'boolean',
            'margin_percent_at_bid' => 'decimal:4',
            'closure_was_allowed' => 'boolean',
            'bid_at' => 'datetime',
        ];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

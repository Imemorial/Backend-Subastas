<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuctionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Auction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'winner_user_id',
        'starting_price',
        'current_price',
        'bid_increment',
        'initial_timer_seconds',
        'timer_extension_seconds',
        'remaining_seconds',
        'total_bids',
        'bits_consumed',
        'min_margin_percent',
        'max_margin_percent',
        'closure_allowed',
        'status',
        'scheduled_at',
        'started_at',
        'ends_at',
        'ended_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starting_price' => 'decimal:2',
            'current_price' => 'decimal:2',
            'bid_increment' => 'decimal:4',
            'min_margin_percent' => 'decimal:2',
            'max_margin_percent' => 'decimal:2',
            'closure_allowed' => 'boolean',
            'status' => AuctionStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }
}

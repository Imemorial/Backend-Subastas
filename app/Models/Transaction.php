<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'bit_pack_id',
        'type',
        'status',
        'bits_delta',
        'amount_eur',
        'currency',
        'reference_type',
        'reference_id',
        'payment_provider',
        'payment_intent_id',
        'idempotency_key',
        'metadata',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'bits_delta' => 'integer',
            'amount_eur' => 'decimal:2',
            'metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bitPack(): BelongsTo
    {
        return $this->belongsTo(BitPack::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

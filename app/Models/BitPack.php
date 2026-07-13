<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BitPack extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'bits_amount',
        'price_eur',
        'bit_unit_price',
        'bonus_bits',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price_eur' => 'decimal:2',
            'bit_unit_price' => 'decimal:4',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}

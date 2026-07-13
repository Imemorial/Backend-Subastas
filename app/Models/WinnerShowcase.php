<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WinnerShowcase extends Model
{
    protected $fillable = [
        'winner_name',
        'product_name',
        'short_description',
        'image_path',
        'final_price',
        'retail_value',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'final_price' => 'decimal:2',
            'retail_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}

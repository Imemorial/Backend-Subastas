<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'real_cost',
        'retail_value',
        'sku',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'real_cost' => 'decimal:2',
            'retail_value' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }
}
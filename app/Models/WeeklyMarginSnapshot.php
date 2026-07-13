<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyMarginSnapshot extends Model
{
    protected $fillable = [
        'iso_year',
        'iso_week',
        'week_start',
        'week_end',
        'auctions_ended',
        'total_bits_consumed',
        'total_bit_revenue',
        'total_closing_prices',
        'total_real_cost',
        'total_revenue',
        'margin_percent',
        'target_min_margin',
        'target_max_margin',
        'balancer_state',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'total_bit_revenue' => 'decimal:2',
            'total_closing_prices' => 'decimal:2',
            'total_real_cost' => 'decimal:2',
            'total_revenue' => 'decimal:2',
            'margin_percent' => 'decimal:4',
            'target_min_margin' => 'decimal:2',
            'target_max_margin' => 'decimal:2',
            'balancer_state' => 'array',
        ];
    }
}

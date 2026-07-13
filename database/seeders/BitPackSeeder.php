<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BitPack;
use Illuminate\Database\Seeder;

class BitPackSeeder extends Seeder
{
    public function run(): void
    {
        $packs = [
            ['name' => 'Starter', 'slug' => 'starter', 'bits_amount' => 50, 'price_eur' => 10.00, 'sort_order' => 1],
            ['name' => 'Popular', 'slug' => 'popular', 'bits_amount' => 150, 'price_eur' => 30.00, 'sort_order' => 2, 'is_featured' => true],
            ['name' => 'Pro', 'slug' => 'pro', 'bits_amount' => 500, 'price_eur' => 100.00, 'sort_order' => 3, 'bonus_bits' => 25],
            ['name' => 'Elite', 'slug' => 'elite', 'bits_amount' => 1200, 'price_eur' => 240.00, 'sort_order' => 4, 'bonus_bits' => 100],
        ];

        foreach ($packs as $pack) {
            BitPack::query()->updateOrCreate(
                ['slug' => $pack['slug']],
                array_merge([
                    'bit_unit_price' => 0.20,
                    'is_active' => true,
                    'is_featured' => false,
                    'bonus_bits' => 0,
                ], $pack),
            );
        }
    }
}

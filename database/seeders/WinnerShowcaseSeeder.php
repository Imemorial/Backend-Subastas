<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WinnerShowcase;
use Illuminate\Database\Seeder;

final class WinnerShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $showcases = [
            [
                'winner_name' => 'Laura M.',
                'product_name' => 'iPhone 15 Pro Max',
                'short_description' => '¡Flipé! Pensé que era broma hasta que me llegó a casa.',
                'final_price' => 14.80,
                'retail_value' => 1299.00,
                'sort_order' => 1,
            ],
            [
                'winner_name' => 'Carlos R.',
                'product_name' => 'PlayStation 5 Slim',
                'short_description' => 'Mi primera puja y ya gané. Ahora mis amigos quieren entrar.',
                'final_price' => 9.40,
                'retail_value' => 549.99,
                'sort_order' => 2,
            ],
            [
                'winner_name' => 'Sofía G.',
                'product_name' => 'MacBook Air M3',
                'short_description' => 'Menos de lo que me gasto en cafés en un mes. Increíble.',
                'final_price' => 22.60,
                'retail_value' => 1499.00,
                'sort_order' => 3,
            ],
            [
                'winner_name' => 'David P.',
                'product_name' => 'AirPods Pro 2',
                'short_description' => 'Lo vi en directo, pujé un par de veces y listo. Gané.',
                'final_price' => 3.20,
                'retail_value' => 279.00,
                'sort_order' => 4,
            ],
        ];

        foreach ($showcases as $showcase) {
            WinnerShowcase::query()->updateOrCreate(
                [
                    'winner_name' => $showcase['winner_name'],
                    'product_name' => $showcase['product_name'],
                ],
                $showcase,
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoAuctionSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::query()->where('email', 'cliente@bitsauction.test')->first();

        $activeProducts = [
            [
                'name' => 'PlayStation 5 Slim',
                'real_cost' => 380.00,
                'retail_value' => 549.99,
                'description' => 'Consola PS5 Slim con mandos DualSense.',
                'current_price' => 12.45,
                'total_bids' => 850,
            ],
            [
                'name' => 'iPhone 15 Pro Max 256GB',
                'real_cost' => 950.00,
                'retail_value' => 1469.00,
                'description' => 'Smartphone Apple última generación.',
                'current_price' => 8.20,
                'total_bids' => 1200,
            ],
            [
                'name' => 'MacBook Air M3 15"',
                'real_cost' => 1050.00,
                'retail_value' => 1599.00,
                'description' => 'Portátil ultraligero con chip M3.',
                'current_price' => 22.15,
                'total_bids' => 2100,
            ],
        ];

        foreach ($activeProducts as $data) {
            $product = Product::query()->withTrashed()->updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'real_cost' => $data['real_cost'],
                    'retail_value' => $data['retail_value'],
                    'status' => 'published',
                    'deleted_at' => null,
                ],
            );

            Auction::query()->updateOrCreate(
                ['product_id' => $product->id, 'status' => AuctionStatus::Active],
                [
                    'starting_price' => 0,
                    'current_price' => $data['current_price'],
                    'bid_increment' => 0.20,
                    'initial_timer_seconds' => 15,
                    'timer_extension_seconds' => 10,
                    'remaining_seconds' => 15,
                    'total_bids' => $data['total_bids'],
                    'bits_consumed' => $data['total_bids'],
                    'started_at' => now()->subMinutes(30),
                    'ends_at' => now()->addSeconds(15),
                    'closure_allowed' => false,
                ],
            );
        }

        $endedProducts = [
            ['name' => 'AirPods Pro 2', 'retail_value' => 279.00, 'real_cost' => 180.00, 'final_price' => 4.87, 'total_bids' => 487],
            ['name' => 'Nintendo Switch OLED', 'retail_value' => 349.99, 'real_cost' => 260.00, 'final_price' => 6.12, 'total_bids' => 612],
            ['name' => 'Samsung Galaxy S24 Ultra', 'retail_value' => 1299.00, 'real_cost' => 820.00, 'final_price' => 11.34, 'total_bids' => 1134],
            ['name' => 'iPad Air M2', 'retail_value' => 749.00, 'real_cost' => 480.00, 'final_price' => 7.55, 'total_bids' => 755],
        ];

        $scheduledProducts = [
            [
                'name' => 'Apple Watch Ultra 2',
                'real_cost' => 620.00,
                'retail_value' => 899.00,
                'description' => 'Reloj inteligente resistente con GPS dual.',
                'scheduled_at' => now()->addHours(6),
            ],
            [
                'name' => 'Dyson V15 Detect',
                'real_cost' => 480.00,
                'retail_value' => 749.00,
                'description' => 'Aspirador inalámbrico con detección láser de polvo.',
                'scheduled_at' => now()->addDay(),
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'real_cost' => 260.00,
                'retail_value' => 399.00,
                'description' => 'Auriculares premium con cancelación de ruido.',
                'scheduled_at' => now()->addDays(2),
            ],
            [
                'name' => 'GoPro Hero 13 Black',
                'real_cost' => 320.00,
                'retail_value' => 499.00,
                'description' => 'Cámara de acción 5.3K con estabilización HyperSmooth.',
                'scheduled_at' => now()->addDays(3),
            ],
        ];

        foreach ($scheduledProducts as $data) {
            $product = Product::query()->withTrashed()->updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'real_cost' => $data['real_cost'],
                    'retail_value' => $data['retail_value'],
                    'status' => 'published',
                    'deleted_at' => null,
                ],
            );

            Auction::query()->updateOrCreate(
                ['product_id' => $product->id, 'status' => AuctionStatus::Scheduled],
                [
                    'starting_price' => 0,
                    'current_price' => 0,
                    'bid_increment' => 0.20,
                    'initial_timer_seconds' => 15,
                    'timer_extension_seconds' => 10,
                    'remaining_seconds' => 15,
                    'total_bids' => 0,
                    'bits_consumed' => 0,
                    'scheduled_at' => $data['scheduled_at'],
                    'closure_allowed' => false,
                ],
            );
        }

        foreach ($endedProducts as $index => $data) {
            $product = Product::query()->withTrashed()->updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => 'Producto subastado recientemente.',
                    'real_cost' => $data['real_cost'],
                    'retail_value' => $data['retail_value'],
                    'status' => 'published',
                    'deleted_at' => null,
                ],
            );

            Auction::query()->updateOrCreate(
                ['product_id' => $product->id, 'status' => AuctionStatus::Ended],
                [
                    'winner_user_id' => $client?->id,
                    'starting_price' => 0,
                    'current_price' => $data['final_price'],
                    'bid_increment' => 0.20,
                    'initial_timer_seconds' => 15,
                    'timer_extension_seconds' => 10,
                    'remaining_seconds' => 0,
                    'total_bids' => $data['total_bids'],
                    'bits_consumed' => $data['total_bids'],
                    'started_at' => now()->subDays($index + 2),
                    'ends_at' => now()->subDays($index + 1),
                    'ended_at' => now()->subHours($index + 1),
                    'closure_allowed' => true,
                ],
            );
        }
    }
}

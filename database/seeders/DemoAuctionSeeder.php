<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AuctionStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoAuctionSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = dirname(base_path())
            .DIRECTORY_SEPARATOR.'Frontend-Subastas'
            .DIRECTORY_SEPARATOR.'src'
            .DIRECTORY_SEPARATOR.'app'
            .DIRECTORY_SEPARATOR.'assets'
            .DIRECTORY_SEPARATOR.'test-prod';

        if (! is_dir($sourceDir)) {
            $this->command?->error("No se encuentra la carpeta de imágenes: {$sourceDir}");

            return;
        }

        Auction::query()->delete();
        ProductImage::query()->delete();
        Product::withTrashed()->forceDelete();

        $winners = $this->ensureWinnerUsers();

        // Esta semana (activas ahora)
        $thisWeek = [
            [
                'name' => 'iPhone 17 Pro Naranja',
                'file' => 'descarga.webp',
                'real_cost' => 980.00,
                'retail_value' => 1399.00,
                'description' => 'Smartphone Apple Pro en acabado naranja, triple cámara y pantalla Super Retina.',
                'current_price' => 11.80,
                'total_bids' => 1180,
            ],
            [
                'name' => 'MacBook Air Midnight',
                'file' => 'descarga (3).webp',
                'real_cost' => 1050.00,
                'retail_value' => 1599.00,
                'description' => 'Portátil ultraligero Apple en color Midnight, ideal para trabajo y creación.',
                'current_price' => 19.40,
                'total_bids' => 1940,
            ],
            [
                'name' => 'Samsung Mini LED 55" M72H',
                'file' => 'es-mini-led-m70h-588518-tu55m72hauxxc-552725316.png',
                'real_cost' => 520.00,
                'retail_value' => 899.00,
                'description' => 'Smart TV Samsung Mini LED 55" con Vision AI, Bixby y Samsung TV Plus.',
                'current_price' => 8.60,
                'total_bids' => 860,
            ],
            [
                'name' => 'Meta Quest 3',
                'file' => 'images.jpg',
                'real_cost' => 380.00,
                'retail_value' => 549.99,
                'description' => 'Gafas de realidad mixta Meta Quest 3 con sensores de última generación.',
                'current_price' => 6.20,
                'total_bids' => 620,
            ],
            [
                'name' => 'AirPods Max Space Gray',
                'file' => 'shopping.webp',
                'real_cost' => 420.00,
                'retail_value' => 629.00,
                'description' => 'Auriculares over-ear Apple con cancelación activa de ruido y Audio Espacial.',
                'current_price' => 14.20,
                'total_bids' => 1420,
            ],
        ];

        $nextWeekStart = now()->startOfWeek()->addWeek();

        // Semana que viene (programadas)
        $nextWeek = [
            [
                'name' => 'Frigorífico Cecotec 117L',
                'file' => 'descarga (4).webp',
                'real_cost' => 180.00,
                'retail_value' => 299.00,
                'description' => 'Frigorífico combi Cecotec negro mate 117 L, clase energética E.',
                'scheduled_at' => $nextWeekStart->copy()->setTime(12, 0),
            ],
            [
                'name' => 'Conga WinDroid Limpiacristales',
                'file' => 'descarga (5).webp',
                'real_cost' => 95.00,
                'retail_value' => 179.00,
                'description' => 'Robot limpiacristales Conga WinDroid con mando, app y bayetas incluidas.',
                'scheduled_at' => $nextWeekStart->copy()->addDay()->setTime(18, 0),
            ],
            [
                'name' => 'Silla Ergonómica Ejecutiva',
                'file' => 'descarga (6).webp',
                'real_cost' => 210.00,
                'retail_value' => 399.00,
                'description' => 'Silla de oficina ergonómica con respaldo articulado, reposacabezas y base con ruedas.',
                'scheduled_at' => $nextWeekStart->copy()->addDays(2)->setTime(12, 0),
            ],
            [
                'name' => 'Smartwatch Outdoor Dual Band',
                'file' => 'descarga (7).webp',
                'real_cost' => 55.00,
                'retail_value' => 129.00,
                'description' => 'Reloj inteligente resistente con correa naranja y correa negra de silicona incluida.',
                'scheduled_at' => $nextWeekStart->copy()->addDays(3)->setTime(20, 0),
            ],
            [
                'name' => 'Proyector Inteligente Xiaomi',
                'file' => 'descarga (8).webp',
                'real_cost' => 220.00,
                'retail_value' => 399.00,
                'description' => 'Proyector compacto Xiaomi para cine en casa, diseño minimalista en negro.',
                'scheduled_at' => $nextWeekStart->copy()->addDays(4)->setTime(19, 0),
            ],
        ];

        // Ganadores recientes (el resto)
        $won = [
            [
                'name' => 'AirPods Pro',
                'file' => 'descarga (1).webp',
                'real_cost' => 180.00,
                'retail_value' => 279.00,
                'description' => 'Auriculares inalámbricos con cancelación de ruido y estuche de carga.',
                'final_price' => 4.60,
                'total_bids' => 460,
                'winner' => $winners[0],
            ],
            [
                'name' => 'iPad 10.ª generación',
                'file' => 'descarga (2).webp',
                'real_cost' => 320.00,
                'retail_value' => 499.00,
                'description' => 'iPad 10,9" en color plata, pantalla Liquid Retina y diseño todo pantalla.',
                'final_price' => 9.80,
                'total_bids' => 980,
                'winner' => $winners[1],
            ],
            [
                'name' => 'Altavoz Inteligente Compacto',
                'file' => 'descarga (9).webp',
                'real_cost' => 28.00,
                'retail_value' => 59.99,
                'description' => 'Altavoz smart esférico con anillo LED y controles de volumen en la parte superior.',
                'final_price' => 1.40,
                'total_bids' => 140,
                'winner' => $winners[2],
            ],
            [
                'name' => 'Horno Cecotec Bolero 133L',
                'file' => 'shopping (1).webp',
                'real_cost' => 260.00,
                'retail_value' => 449.00,
                'description' => 'Horno empotrable Cecotec 133 L clase A con modos Airfryer Master y Pizza Master.',
                'final_price' => 12.20,
                'total_bids' => 1220,
                'winner' => $winners[3],
            ],
        ];

        foreach ($thisWeek as $data) {
            $product = $this->createProduct($sourceDir, $data);
            Auction::query()->create([
                'product_id' => $product->id,
                'status' => AuctionStatus::Active,
                'starting_price' => 0,
                'current_price' => $data['current_price'],
                'bid_increment' => 0.20,
                'initial_timer_seconds' => 15,
                'timer_extension_seconds' => 10,
                'remaining_seconds' => 15,
                'total_bids' => $data['total_bids'],
                'bits_consumed' => $data['total_bids'],
                'started_at' => now()->subMinutes(45),
                'ends_at' => now()->addSeconds(15),
                'closure_allowed' => false,
            ]);
        }

        foreach ($nextWeek as $data) {
            $product = $this->createProduct($sourceDir, $data);
            Auction::query()->create([
                'product_id' => $product->id,
                'status' => AuctionStatus::Scheduled,
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
            ]);
        }

        foreach ($won as $index => $data) {
            $product = $this->createProduct($sourceDir, $data);
            Auction::query()->create([
                'product_id' => $product->id,
                'status' => AuctionStatus::Ended,
                'winner_user_id' => $data['winner']->id,
                'starting_price' => 0,
                'current_price' => $data['final_price'],
                'bid_increment' => 0.20,
                'initial_timer_seconds' => 15,
                'timer_extension_seconds' => 10,
                'remaining_seconds' => 0,
                'total_bids' => $data['total_bids'],
                'bits_consumed' => $data['total_bids'],
                'started_at' => now()->subDays($index + 3),
                'ends_at' => now()->subDays($index + 1),
                'ended_at' => now()->subHours(($index + 1) * 6),
                'closure_allowed' => true,
            ]);
        }

        $this->command?->info('Demo cargado: 5 activas (esta semana), 5 programadas (semana que viene), 4 ganadas.');
    }

    /**
     * @param  array{name: string, file: string, real_cost: float, retail_value: float, description: string}  $data
     */
    private function createProduct(string $sourceDir, array $data): Product
    {
        $storedPath = $this->storeImage($sourceDir, $data['file'], $data['name']);

        $product = Product::query()->create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'],
            'image_path' => $storedPath,
            'real_cost' => $data['real_cost'],
            'retail_value' => $data['retail_value'],
            'status' => 'published',
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $storedPath,
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        return $product;
    }

    private function storeImage(string $sourceDir, string $filename, string $productName): string
    {
        $source = $sourceDir.DIRECTORY_SEPARATOR.$filename;
        if (! is_file($source)) {
            throw new \RuntimeException("Imagen no encontrada: {$source}");
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION) ?: 'webp';
        $target = 'products/'.Str::slug($productName).'-'.Str::random(6).'.'.$extension;

        Storage::disk('public')->put($target, File::get($source));

        return $target;
    }

    /** @return list<User> */
    private function ensureWinnerUsers(): array
    {
        $profiles = [
            ['name' => 'Laura M.', 'email' => 'laura.m@bitsauction.test'],
            ['name' => 'Carlos R.', 'email' => 'carlos.r@bitsauction.test'],
            ['name' => 'Sofía G.', 'email' => 'sofia.g@bitsauction.test'],
            ['name' => 'David P.', 'email' => 'david.p@bitsauction.test'],
        ];

        $users = [];
        foreach ($profiles as $profile) {
            $users[] = User::query()->updateOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'password' => Hash::make('password'),
                    'role' => UserRole::Client,
                    'bit_balance' => 50,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        }

        return $users;
    }
}

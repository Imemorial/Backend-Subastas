<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_with_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'password' => Hash::make('password'),
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->post('/api/v1/admin/products', [
            'name' => 'Nintendo Switch OLED',
            'description' => 'Consola portátil',
            'real_cost' => 250.00,
            'retail_value' => 349.99,
            'status' => 'published',
            'image' => UploadedFile::fake()->image('switch.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('product.name', 'Nintendo Switch OLED');

        $this->assertDatabaseHas('products', [
            'name' => 'Nintendo Switch OLED',
            'real_cost' => 250.00,
        ]);
    }

    public function test_client_cannot_create_product(): void
    {
        $client = User::factory()->create([
            'role' => UserRole::Client,
        ]);

        $token = $client->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->post('/api/v1/admin/products', [
            'name' => 'Producto Prohibido',
            'real_cost' => 100,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_product_with_active_auction(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'password' => Hash::make('password'),
        ]);

        $product = \App\Models\Product::query()->create([
            'name' => 'Producto activo',
            'slug' => 'producto-activo',
            'real_cost' => 100,
            'retail_value' => 200,
            'status' => 'published',
        ]);

        $auction = \App\Models\Auction::query()->create([
            'product_id' => $product->id,
            'status' => \App\Enums\AuctionStatus::Active,
            'starting_price' => 0,
            'current_price' => 5,
            'bid_increment' => 0.2,
            'initial_timer_seconds' => 15,
            'timer_extension_seconds' => 10,
            'remaining_seconds' => 15,
            'started_at' => now(),
            'ends_at' => now()->addSeconds(15),
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->delete("/api/v1/admin/products/{$product->id}");

        $response->assertOk();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('auctions', [
            'id' => $auction->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_admin_can_create_product_reusing_slug_from_soft_deleted_product(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'password' => Hash::make('password'),
        ]);

        $deleted = \App\Models\Product::query()->create([
            'name' => 'Apple Watch',
            'slug' => 'apple-watch',
            'real_cost' => 100,
            'retail_value' => 200,
            'status' => 'published',
        ]);
        $deleted->delete();

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->post('/api/v1/admin/products', [
            'name' => 'Apple Watch',
            'real_cost' => 299,
            'retail_value' => 560,
            'status' => 'published',
        ]);

        $response->assertCreated()
            ->assertJsonPath('product.slug', 'apple-watch-1');
    }
}

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
}

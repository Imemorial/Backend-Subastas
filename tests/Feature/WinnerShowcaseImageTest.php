<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WinnerShowcase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class WinnerShowcaseImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_winner_showcase_image_via_post(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'password' => Hash::make('password'),
        ]);

        $showcase = WinnerShowcase::query()->create([
            'winner_name' => 'Laura M.',
            'product_name' => 'iPhone',
            'final_price' => 10,
            'retail_value' => 999,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->post("/api/v1/admin/winner-showcases/{$showcase->id}", [
            'winner_name' => 'Laura M.',
            'image' => UploadedFile::fake()->image('winner.jpg'),
        ]);

        $response->assertOk()
            ->assertJsonPath('showcase.winner_name', 'Laura M.');

        $showcase->refresh();

        $this->assertNotNull($showcase->image_path);
        Storage::disk('public')->assertExists($showcase->image_path);
    }
}

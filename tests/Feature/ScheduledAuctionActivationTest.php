<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScheduledAuctionActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_auction_is_activated_when_due(): void
    {
        $product = Product::query()->create([
            'name' => 'Reloj programado',
            'slug' => 'reloj-programado',
            'real_cost' => 100,
            'retail_value' => 100,
            'status' => 'published',
        ]);

        $auction = Auction::query()->create([
            'product_id' => $product->id,
            'status' => AuctionStatus::Scheduled,
            'starting_price' => 0,
            'current_price' => 0,
            'bid_increment' => 0.2,
            'initial_timer_seconds' => 15,
            'timer_extension_seconds' => 10,
            'remaining_seconds' => 15,
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('auctions:process-timers')->assertSuccessful();

        $auction->refresh();

        $this->assertSame(AuctionStatus::Active, $auction->status);
        $this->assertNotNull($auction->started_at);
        $this->assertNotNull($auction->ends_at);
    }
}

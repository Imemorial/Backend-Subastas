<?php

declare(strict_types=1);

namespace App\Services\Auction;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\Product;

final class AuctionManagementService
{
    public function __construct(
        private readonly AuctionMarginService $marginService,
    ) {}

    public function createFromProduct(Product $product, array $data): Auction
    {
        $productMeta = is_array($product->metadata) ? $product->metadata : [];
        $strategy = $productMeta['strategy'] ?? $this->marginService->calculateRecommendedAuctionStrategy(
            (float) $product->real_cost,
        );

        $plannedBits = (int) ($data['planned_bits']
            ?? $productMeta['estimated_bits']
            ?? $strategy['recommended_bits_target']
            ?? 0);

        $closingPrice = (float) ($data['planned_closing_price']
            ?? $strategy['recommended_customer_price_target']
            ?? 0);

        $startingPrice = (float) ($data['starting_price']
            ?? $strategy['recommended_starting_price']
            ?? 0);

        $bidIncrement = (float) ($data['bid_increment']
            ?? $strategy['suggested_bid_increment']
            ?? config('auction.bit_value_eur', 0.20));

        $projection = $this->marginService->projectFromPlan(
            (float) $product->real_cost,
            $plannedBits,
            $closingPrice,
        );

        $startImmediately = (bool) ($data['start_immediately'] ?? false);
        $initialTimer = (int) ($data['initial_timer_seconds'] ?? 10);
        $status = $startImmediately ? AuctionStatus::Active : AuctionStatus::Scheduled;
        $now = now();

        $auction = Auction::query()->create([
            'product_id' => $product->id,
            'starting_price' => $startingPrice,
            'current_price' => $startingPrice,
            'bid_increment' => $bidIncrement,
            'initial_timer_seconds' => $initialTimer,
            'timer_extension_seconds' => $data['timer_extension_seconds'] ?? 10,
            'remaining_seconds' => $initialTimer,
            'min_margin_percent' => $data['min_margin_percent'] ?? config('auction.margin.auction_min_percent'),
            'max_margin_percent' => $data['max_margin_percent'] ?? config('auction.margin.auction_max_percent'),
            'status' => $status,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'started_at' => $startImmediately ? $now : null,
            'ends_at' => $startImmediately ? $now->copy()->addSeconds($initialTimer) : null,
            'metadata' => [
                'planned_bits' => $plannedBits,
                'planned_closing_price' => $closingPrice,
                'strategy' => $strategy,
                'projection' => $projection,
            ],
        ]);

        $this->marginService->syncClosureFlag($auction);

        return $auction->load('product');
    }

    public function activate(Auction $auction): Auction
    {
        $seconds = (int) $auction->initial_timer_seconds;

        $auction->forceFill([
            'status' => AuctionStatus::Active,
            'started_at' => now(),
            'remaining_seconds' => $seconds,
            'ends_at' => now()->addSeconds($seconds),
        ])->save();

        $this->marginService->syncClosureFlag($auction);

        return $auction->fresh(['product']);
    }

    public function pause(Auction $auction): Auction
    {
        $auction->update(['status' => AuctionStatus::Paused]);

        return $auction->fresh(['product']);
    }

    public function resume(Auction $auction): Auction
    {
        $seconds = max(1, (int) $auction->remaining_seconds);

        $auction->forceFill([
            'status' => AuctionStatus::Active,
            'ends_at' => now()->addSeconds($seconds),
        ])->save();

        return $auction->fresh(['product']);
    }
}

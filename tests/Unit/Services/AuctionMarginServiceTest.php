<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Auction;
use App\Models\Product;
use App\Services\Auction\AuctionMarginService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AuctionMarginServiceTest extends TestCase
{
    private AuctionMarginService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'auction.bit_value_eur' => 0.20,
            'auction.margin.auction_min_percent' => 17.0,
            'auction.margin.auction_max_percent' => 25.0,
            'auction.margin.weekly_min_percent' => 17.0,
            'auction.margin.weekly_max_percent' => 20.0,
            'auction.margin.weekly_primary' => false,
        ]);
        $this->service = new AuctionMarginService();
    }

    #[DataProvider('marginScenariosProvider')]
    public function test_evaluates_margin_correctly(
        float $realCost,
        int $bitsConsumed,
        float $currentPrice,
        bool $expectedCanClose,
    ): void {
        $result = $this->service->evaluateFromMetrics(
            realCost: $realCost,
            bitsConsumed: $bitsConsumed,
            currentPrice: $currentPrice,
        );

        $this->assertSame($expectedCanClose, $result->canClose);
    }

    public static function marginScenariosProvider(): array
    {
        return [
            'below minimum margin' => [1000.0, 100, 50.0, false],
            'within range (18%)' => [500.0, 2200, 150.0, true],
            'above maximum margin' => [500.0, 3000, 45.0, false],
        ];
    }

    public function test_margin_formula_example(): void
    {
        // Producto costo real: 500€
        // 3000 bits consumidos × 0.20€ = 600€
        // Precio cierre: 45€
        // Ingresos: 645€ → beneficio 145€ → margen 29% (por encima del máximo)
        $result = $this->service->evaluateFromMetrics(
            realCost: 500.0,
            bitsConsumed: 3000,
            currentPrice: 45.0,
        );

        $this->assertEqualsWithDelta(29.0, $result->marginPercent, 0.1);
        $this->assertFalse($result->canClose);
    }

    public function test_margin_within_target_can_close(): void
    {
        // Costo: 500€, bits: 2200 (440€), precio: 150€ → 590€ → 18% margen
        $result = $this->service->evaluateFromMetrics(
            realCost: 500.0,
            bitsConsumed: 2200,
            currentPrice: 150.0,
        );

        $this->assertEqualsWithDelta(18.0, $result->marginPercent, 0.1);
        $this->assertTrue($result->canClose);
    }

    public function test_timer_expiry_extends_when_margin_is_below_minimum(): void
    {
        $auction = $this->makeAuction(realCost: 100.0, bitsConsumed: 100, currentPrice: 5.0);

        $resolution = $this->service->resolveTimerOnExpiry($auction);

        $this->assertFalse($resolution->shouldEnd);
        $this->assertTrue($resolution->evaluation->shouldExtendTimer);
        $this->assertSame('margin_below_minimum', $resolution->evaluation->reason);
    }

    public function test_timer_expiry_closes_when_margin_is_within_range(): void
    {
        $auction = $this->makeAuction(realCost: 100.0, bitsConsumed: 500, currentPrice: 19.0);

        $resolution = $this->service->resolveTimerOnExpiry($auction);

        $this->assertTrue($resolution->shouldEnd);
        $this->assertTrue($resolution->evaluation->canClose);
        $this->assertSame('margin_within_range', $resolution->evaluation->reason);
    }

    public function test_bid_is_rejected_when_auction_is_already_in_closure_window(): void
    {
        $auction = $this->makeAuction(
            realCost: 100.0,
            bitsConsumed: 500,
            currentPrice: 19.0,
            closureAllowed: true,
        );

        $this->assertFalse($this->service->canAcceptBid($auction));
    }

    public function test_bid_is_rejected_when_next_bid_would_exceed_max_margin(): void
    {
        config(['auction.margin.weekly_primary' => false]);

        $auction = $this->makeAuction(
            realCost: 100.0,
            bitsConsumed: 610,
            currentPrice: 17.99,
            bidIncrement: 0.01,
        );

        $nextBid = $this->service->evaluateNextBid($auction);

        $this->assertGreaterThan(25.0, $nextBid->marginPercent);
        $this->assertFalse($this->service->canAcceptBid($auction));
    }

    public function test_weekly_primary_allows_bids_even_if_per_auction_margin_exceeds_max(): void
    {
        config(['auction.margin.weekly_primary' => true]);
        $service = new AuctionMarginService();

        $auction = $this->makeAuction(
            realCost: 100.0,
            bitsConsumed: 610,
            currentPrice: 17.99,
            bidIncrement: 0.01,
        );

        $this->assertTrue($service->canAcceptBid($auction));
    }

    private function makeAuction(
        float $realCost,
        int $bitsConsumed,
        float $currentPrice,
        float $bidIncrement = 0.01,
        bool $closureAllowed = false,
    ): Auction {
        $product = new Product([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'real_cost' => $realCost,
            'status' => 'published',
        ]);

        $auction = new Auction([
            'bits_consumed' => $bitsConsumed,
            'current_price' => $currentPrice,
            'bid_increment' => $bidIncrement,
            'min_margin_percent' => 17.0,
            'max_margin_percent' => 25.0,
            'closure_allowed' => $closureAllowed,
        ]);

        $auction->setRelation('product', $product);

        return $auction;
    }
}

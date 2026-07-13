<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Auction\MarginEvaluationResult;
use App\DTOs\Auction\WeeklyMarginReport;
use App\Models\Auction;
use App\Models\Product;
use App\Services\Auction\AuctionMarginService;
use App\Services\Auction\WeeklyMarginBalancerService;
use Tests\TestCase;

final class WeeklyMarginBalancerServiceTest extends TestCase
{
    private WeeklyMarginBalancerService $balancer;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'auction.bit_value_eur' => 0.20,
            'auction.margin.weekly_min_percent' => 17.0,
            'auction.margin.weekly_max_percent' => 20.0,
            'auction.margin.subsidy_bank_min_percent' => 17.0,
            'auction.margin.weekly_primary' => true,
        ]);

        $this->balancer = new WeeklyMarginBalancerService(new AuctionMarginService());
    }

    public function test_allows_subsidized_close_when_weekly_bank_is_healthy(): void
    {
        $report = new WeeklyMarginReport(
            isoYear: 2026,
            isoWeek: 28,
            marginPercent: 22.0,
            targetMinMargin: 17.0,
            targetMaxMargin: 20.0,
            isWithinTarget: false,
            adjustmentFactor: 1.0,
            metrics: [
                'auctions_ended' => 1,
                'total_bits_consumed' => 50000,
                'total_bit_revenue' => 10000.0,
                'total_closing_prices' => 500.0,
                'total_real_cost' => 8000.0,
                'total_revenue' => 10500.0,
            ],
        );

        $auction = $this->makeAuction(realCost: 800.0, bitsConsumed: 50, currentPrice: 10.0);
        $evaluation = new MarginEvaluationResult(
            bitRevenue: 10.0,
            closingPriceRevenue: 10.0,
            totalRevenue: 20.0,
            realCost: 800.0,
            netProfit: -780.0,
            marginPercent: -97.5,
            canClose: false,
            shouldExtendTimer: true,
            reason: 'margin_below_minimum',
        );

        $this->assertTrue($this->balancer->canAllowSubsidizedClose($report, $auction, $evaluation));
    }

    public function test_blocks_subsidized_close_without_weekly_bank(): void
    {
        $report = new WeeklyMarginReport(
            isoYear: 2026,
            isoWeek: 28,
            marginPercent: 0.0,
            targetMinMargin: 17.0,
            targetMaxMargin: 20.0,
            isWithinTarget: false,
            adjustmentFactor: 1.0,
            metrics: [
                'auctions_ended' => 0,
                'total_bits_consumed' => 0,
                'total_bit_revenue' => 0.0,
                'total_closing_prices' => 0.0,
                'total_real_cost' => 0.0,
                'total_revenue' => 0.0,
            ],
        );

        $auction = $this->makeAuction(realCost: 800.0, bitsConsumed: 50, currentPrice: 10.0);
        $evaluation = new MarginEvaluationResult(
            bitRevenue: 10.0,
            closingPriceRevenue: 10.0,
            totalRevenue: 20.0,
            realCost: 800.0,
            netProfit: -780.0,
            marginPercent: -97.5,
            canClose: false,
            shouldExtendTimer: true,
            reason: 'margin_below_minimum',
        );

        $this->assertFalse($this->balancer->canAllowSubsidizedClose($report, $auction, $evaluation));
    }

    private function makeAuction(float $realCost, int $bitsConsumed, float $currentPrice): Auction
    {
        $product = new Product([
            'name' => 'iPhone',
            'slug' => 'iphone',
            'real_cost' => $realCost,
            'status' => 'published',
        ]);

        $auction = new Auction([
            'bits_consumed' => $bitsConsumed,
            'current_price' => $currentPrice,
            'bid_increment' => 0.01,
            'min_margin_percent' => 17.0,
            'max_margin_percent' => 25.0,
            'closure_allowed' => false,
        ]);

        $auction->setRelation('product', $product);

        return $auction;
    }
}

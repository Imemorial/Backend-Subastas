<?php

declare(strict_types=1);

namespace App\Services\Auction;

use App\Models\Auction;
use Carbon\Carbon;

final class WeeklySchedulePlannerService
{
    public function __construct(
        private readonly WeeklyMarginBalancerService $marginBalancer,
        private readonly AuctionMarginService $marginService,
    ) {}

    public function buildPlan(?Carbon $referenceDate = null): array
    {
        $date = $referenceDate ?? now();
        $actualReport = $this->marginBalancer->buildWeeklyReport($date);
        $actual = $actualReport->toArray();
        $actualMetrics = $actual['metrics'];
        $actualMetrics['net_profit'] = round(
            (float) $actualMetrics['total_revenue'] - (float) $actualMetrics['total_real_cost'],
            2,
        );

        $weekStart = $date->copy()->startOfWeek();
        $weekEnd = $date->copy()->endOfWeek();

        $scheduledAuctions = Auction::query()
            ->with('product')
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [$weekStart, $weekEnd])
            ->orderBy('scheduled_at')
            ->get();

        $scheduledItems = [];
        $plannedCost = 0.0;
        $plannedRevenue = 0.0;
        $plannedProfit = 0.0;

        foreach ($scheduledAuctions as $auction) {
            if ($auction->product === null) {
                continue;
            }

            $item = $this->buildScheduledItem($auction);
            $scheduledItems[] = $item;
            $plannedCost += (float) $auction->product->real_cost;
            $plannedRevenue += (float) $item['projection']['total_revenue'];
            $plannedProfit += (float) $item['projection']['net_profit'];
        }

        $combinedCost = (float) $actualMetrics['total_real_cost'] + $plannedCost;
        $combinedRevenue = (float) $actualMetrics['total_revenue'] + $plannedRevenue;
        $combinedProfit = (float) $actualMetrics['net_profit'] + $plannedProfit;
        $combinedMargin = $combinedCost > 0 ? ($combinedProfit / $combinedCost) * 100 : 0.0;

        $targetMin = (float) config('auction.margin.weekly_min_percent', 17.0);
        $targetMax = (float) config('auction.margin.weekly_max_percent', 25.0);

        return [
            'iso_year' => (int) $date->isoWeekYear(),
            'iso_week' => (int) $date->isoWeek(),
            'target_min_margin' => $targetMin,
            'target_max_margin' => $targetMax,
            'actual' => array_merge($actual, [
                'metrics' => $actualMetrics,
            ]),
            'scheduled' => $scheduledItems,
            'combined' => [
                'auctions_ended' => (int) $actualMetrics['auctions_ended'],
                'auctions_scheduled' => count($scheduledItems),
                'total_real_cost' => round($combinedCost, 2),
                'total_revenue' => round($combinedRevenue, 2),
                'net_profit' => round($combinedProfit, 2),
                'margin_percent' => round($combinedMargin, 2),
                'is_within_target' => $combinedMargin >= $targetMin && $combinedMargin <= $targetMax,
            ],
        ];
    }

    private function buildScheduledItem(Auction $auction): array
    {
        $product = $auction->product;
        $metadata = is_array($auction->metadata) ? $auction->metadata : [];
        $productMeta = is_array($product->metadata) ? $product->metadata : [];
        $strategy = $metadata['strategy']
            ?? $productMeta['strategy']
            ?? $this->marginService->calculateRecommendedAuctionStrategy((float) $product->real_cost);

        $plannedBits = (int) ($metadata['planned_bits']
            ?? $productMeta['estimated_bits']
            ?? $strategy['recommended_bits_target']
            ?? 0);

        $closingPrice = (float) ($metadata['planned_closing_price']
            ?? $strategy['recommended_customer_price_target']
            ?? 0);

        $projection = $this->marginService->projectFromPlan(
            (float) $product->real_cost,
            $plannedBits,
            $closingPrice,
        );

        return [
            'auction_id' => $auction->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'real_cost' => (float) $product->real_cost,
            'scheduled_at' => $auction->scheduled_at?->toIso8601String(),
            'planned_bits' => $plannedBits,
            'planned_closing_price' => $closingPrice,
            'product_role' => $strategy['product_role'] ?? 'unknown',
            'projection' => $projection,
            'is_margin_viable' => $projection['margin_percent'] >= (float) config('auction.margin.weekly_min_percent', 17.0)
                || ($strategy['product_role'] ?? '') === 'attractor',
        ];
    }
}

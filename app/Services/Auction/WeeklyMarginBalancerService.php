<?php

declare(strict_types=1);

namespace App\Services\Auction;

use App\DTOs\Auction\MarginEvaluationResult;
use App\DTOs\Auction\WeeklyMarginReport;
use App\Models\Auction;
use App\Models\WeeklyMarginSnapshot;
use Carbon\Carbon;

/**
 * Balancea el margen global semanal entre 17% y 25%.
 * Ajusta dinámicamente los umbrales de cierre de subastas activas.
 */
final class WeeklyMarginBalancerService
{
    private readonly float $weeklyMinPercent;
    private readonly float $weeklyMaxPercent;
    private readonly float $subsidyBankMinPercent;
    private readonly float $bitValueEur;
    private readonly bool $weeklyPrimary;

    public function __construct(
        private readonly AuctionMarginService $marginService,
    ) {
        $this->weeklyMinPercent = (float) config('auction.margin.weekly_min_percent', 17.0);
        $this->weeklyMaxPercent = (float) config('auction.margin.weekly_max_percent', 20.0);
        $this->subsidyBankMinPercent = (float) config('auction.margin.subsidy_bank_min_percent', 17.0);
        $this->bitValueEur = (float) config('auction.bit_value_eur', 0.20);
        $this->weeklyPrimary = (bool) config('auction.margin.weekly_primary', true);
    }

    public function isWeeklyPrimary(): bool
    {
        return $this->weeklyPrimary;
    }

    public function buildWeeklyReport(?Carbon $referenceDate = null): WeeklyMarginReport
    {
        $date = $referenceDate ?? now();
        $weekStart = $date->copy()->startOfWeek();
        $weekEnd = $date->copy()->endOfWeek();

        $endedAuctions = Auction::query()
            ->with('product')
            ->where('status', 'ended')
            ->whereBetween('ended_at', [$weekStart, $weekEnd])
            ->get();

        $totalBitRevenue = 0.0;
        $totalClosingPrices = 0.0;
        $totalRealCost = 0.0;
        $totalBits = 0;

        foreach ($endedAuctions as $auction) {
            if ($auction->product === null) {
                continue;
            }

            $totalBits += (int) $auction->bits_consumed;
            $totalBitRevenue += $auction->bits_consumed * $this->bitValueEur;
            $totalClosingPrices += (float) $auction->current_price;
            $totalRealCost += (float) $auction->product->real_cost;
        }

        $totalRevenue = $totalBitRevenue + $totalClosingPrices;
        $netProfit = $totalRevenue - $totalRealCost;
        $marginPercent = $totalRealCost > 0
            ? (($totalRevenue - $totalRealCost) / $totalRealCost) * 100
            : 0.0;

        $isWithinTarget = $marginPercent >= $this->weeklyMinPercent
            && $marginPercent <= $this->weeklyMaxPercent;

        $adjustmentFactor = $this->calculateAdjustmentFactor($marginPercent);

        return new WeeklyMarginReport(
            isoYear: (int) $date->isoWeekYear(),
            isoWeek: (int) $date->isoWeek(),
            marginPercent: $marginPercent,
            targetMinMargin: $this->weeklyMinPercent,
            targetMaxMargin: $this->weeklyMaxPercent,
            isWithinTarget: $isWithinTarget,
            adjustmentFactor: $adjustmentFactor,
            metrics: [
                'auctions_ended' => $endedAuctions->count(),
                'total_bits_consumed' => $totalBits,
                'total_bit_revenue' => round($totalBitRevenue, 2),
                'total_closing_prices' => round($totalClosingPrices, 2),
                'total_real_cost' => round($totalRealCost, 2),
                'total_revenue' => round($totalRevenue, 2),
                'net_profit' => round($netProfit, 2),
            ],
        );
    }

    /**
     * Aplica ajuste fino a subastas activas según el margen semanal global.
     * Factor < 1 endurece cierre (sube min_margin); factor > 1 lo flexibiliza.
     */
    public function balanceActiveAuctions(?Carbon $referenceDate = null): WeeklyMarginReport
    {
        $report = $this->buildWeeklyReport($referenceDate);
        $factor = $report->adjustmentFactor;

        $baseMin = (float) config('auction.margin.auction_min_percent', 17.0);
        $baseMax = (float) config('auction.margin.auction_max_percent', 25.0);

        $adjustedMin = $this->clamp($baseMin * $factor, 15.0, 22.0);
        $adjustedMax = $this->clamp($baseMax * $factor, 20.0, 28.0);

        Auction::query()
            ->where('status', 'active')
            ->chunkById(50, function ($auctions) use ($adjustedMin, $adjustedMax): void {
                foreach ($auctions as $auction) {
                    $auction->forceFill([
                        'min_margin_percent' => round($adjustedMin, 2),
                        'max_margin_percent' => round($adjustedMax, 2),
                    ])->save();

                    $this->marginService->syncClosureFlag($auction);
                }
            });

        $this->persistSnapshot($report);

        return $report;
    }

    /**
     * Proyecta el margen semanal si la subasta activa cerrara ahora con su estado actual.
     */
    public function projectMarginIfAuctionCloses(
        WeeklyMarginReport $report,
        Auction $auction,
        MarginEvaluationResult $evaluation,
    ): float {
        $auctionCost = (float) ($auction->product->real_cost ?? 0);

        if ($auctionCost <= 0) {
            return $report->marginPercent;
        }

        $endedCost = (float) $report->metrics['total_real_cost'];
        $endedRevenue = (float) $report->metrics['total_revenue'];
        $projectedCost = $endedCost + $auctionCost;
        $projectedRevenue = $endedRevenue + $evaluation->totalRevenue;

        return (($projectedRevenue - $projectedCost) / $projectedCost) * 100;
    }

    /**
     * Permite cerrar una subasta por debajo del margen individual si la semana ya tiene
     * margen acumulado y el cierre no rompe el objetivo semanal.
     */
    public function canAllowSubsidizedClose(
        WeeklyMarginReport $report,
        Auction $auction,
        MarginEvaluationResult $evaluation,
    ): bool {
        if (! $this->weeklyPrimary) {
            return false;
        }

        if ($evaluation->canClose) {
            return false;
        }

        $endedCost = (float) $report->metrics['total_real_cost'];
        $endedRevenue = (float) $report->metrics['total_revenue'];

        if ($endedCost <= 0 || $endedRevenue <= 0) {
            return false;
        }

        if ($report->marginPercent < $this->subsidyBankMinPercent) {
            return false;
        }

        $projectedMargin = $this->projectMarginIfAuctionCloses($report, $auction, $evaluation);

        return $projectedMargin >= $this->weeklyMinPercent
            && $projectedMargin <= ($this->weeklyMaxPercent + 5.0);
    }

    public function shouldExtendForWeeklyTarget(
        WeeklyMarginReport $report,
        Auction $auction,
        MarginEvaluationResult $evaluation,
    ): bool {
        if (! $this->weeklyPrimary) {
            return $evaluation->shouldExtendTimer;
        }

        if ($evaluation->canClose) {
            return false;
        }

        if ($this->canAllowSubsidizedClose($report, $auction, $evaluation)) {
            return false;
        }

        $projectedMargin = $this->projectMarginIfAuctionCloses($report, $auction, $evaluation);

        return $projectedMargin < $this->weeklyMinPercent;
    }

    private function calculateAdjustmentFactor(float $currentMarginPercent): float
    {
        if ($currentMarginPercent < $this->weeklyMinPercent) {
            $gap = $this->weeklyMinPercent - $currentMarginPercent;

            return 1.0 + min($gap / 10, 0.15);
        }

        if ($currentMarginPercent > $this->weeklyMaxPercent) {
            $gap = $currentMarginPercent - $this->weeklyMaxPercent;

            return 1.0 - min($gap / 10, 0.12);
        }

        return 1.0;
    }

    private function persistSnapshot(WeeklyMarginReport $report): void
    {
        $now = now();
        $weekStart = $now->copy()->startOfWeek()->toDateString();
        $weekEnd = $now->copy()->endOfWeek()->toDateString();

        WeeklyMarginSnapshot::query()->updateOrCreate(
            [
                'iso_year' => $report->isoYear,
                'iso_week' => $report->isoWeek,
            ],
            [
                'week_start' => $weekStart,
                'week_end' => $weekEnd,
                'auctions_ended' => $report->metrics['auctions_ended'],
                'total_bits_consumed' => $report->metrics['total_bits_consumed'],
                'total_bit_revenue' => $report->metrics['total_bit_revenue'],
                'total_closing_prices' => $report->metrics['total_closing_prices'],
                'total_real_cost' => $report->metrics['total_real_cost'],
                'total_revenue' => $report->metrics['total_revenue'],
                'margin_percent' => $report->marginPercent,
                'target_min_margin' => $report->targetMinMargin,
                'target_max_margin' => $report->targetMaxMargin,
                'balancer_state' => $report->toArray(),
            ],
        );
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}

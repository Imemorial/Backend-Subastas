<?php

declare(strict_types=1);

namespace App\Services\Auction;

use App\DTOs\Auction\MarginEvaluationResult;
use App\Models\Auction;

/**
 * Motor de margen por subasta.
 *
 * Fórmula:
 *   ingresos = (bits_consumidos × valor_bit) + precio_cierre_actual
 *   beneficio_neto = ingresos − costo_real_producto
 *   margen_% = (beneficio_neto / costo_real) × 100
 *
 * Regla de oro: el margen semanal (17–25%) manda. Una subasta puede cerrar barata
 * si otras ya han generado margen en la misma semana.
 */
final class AuctionMarginService
{
    private readonly float $bitValueEur;
    private readonly float $defaultMinMarginPercent;
    private readonly float $defaultMaxMarginPercent;
    private readonly float $weeklyMinMarginPercent;
    private readonly float $weeklyMaxMarginPercent;
    private readonly int $retentionExtensionSeconds;
    private readonly bool $weeklyPrimary;
    private readonly float $retailMultiplier;
    private readonly float $marginCarrierCostThreshold;

    public function __construct()
    {
        $this->bitValueEur = (float) config('auction.bit_value_eur', 0.20);
        $this->defaultMinMarginPercent = (float) config('auction.margin.auction_min_percent', 17.0);
        $this->defaultMaxMarginPercent = (float) config('auction.margin.auction_max_percent', 25.0);
        $this->weeklyMinMarginPercent = (float) config('auction.margin.weekly_min_percent', 17.0);
        $this->weeklyMaxMarginPercent = (float) config('auction.margin.weekly_max_percent', 25.0);
        $this->retentionExtensionSeconds = (int) config('auction.timer.retention_extension_seconds', 8);
        $this->weeklyPrimary = (bool) config('auction.margin.weekly_primary', true);
        $this->retailMultiplier = (float) config('auction.pricing.retail_multiplier', 1.85);
        $this->marginCarrierCostThreshold = (float) config('auction.pricing.margin_carrier_cost_threshold', 500.0);
    }

    public function evaluate(Auction $auction): MarginEvaluationResult
    {
        $evaluation = $this->evaluateAuctionMetrics($auction);

        return $this->applyWeeklyClosureContext($auction, $evaluation);
    }

    public function evaluateAuctionMetrics(Auction $auction): MarginEvaluationResult
    {
        $realCost = (float) $auction->product->real_cost;
        $bitsConsumed = (int) $auction->bits_consumed;
        $currentPrice = (float) $auction->current_price;

        $minMargin = (float) ($auction->min_margin_percent ?? $this->defaultMinMarginPercent);
        $maxMargin = (float) ($auction->max_margin_percent ?? $this->defaultMaxMarginPercent);

        return $this->evaluateFromMetrics(
            realCost: $realCost,
            bitsConsumed: $bitsConsumed,
            currentPrice: $currentPrice,
            minMarginPercent: $minMargin,
            maxMarginPercent: $maxMargin,
        );
    }

    /**
     * Evalúa el margen simulando la siguiente puja (útil antes de persistir).
     */
    public function evaluateNextBid(Auction $auction, int $additionalBits = 1): MarginEvaluationResult
    {
        $realCost = (float) $auction->product->real_cost;
        $simulatedBits = (int) $auction->bits_consumed + $additionalBits;
        $priceStep = $additionalBits * (float) $auction->bid_increment;
        $simulatedPrice = (float) $auction->current_price + $priceStep;

        $minMargin = (float) ($auction->min_margin_percent ?? $this->defaultMinMarginPercent);
        $maxMargin = (float) ($auction->max_margin_percent ?? $this->defaultMaxMarginPercent);

        $evaluation = $this->evaluateFromMetrics(
            realCost: $realCost,
            bitsConsumed: $simulatedBits,
            currentPrice: $simulatedPrice,
            minMarginPercent: $minMargin,
            maxMarginPercent: $maxMargin,
        );

        return $this->applyWeeklyClosureContext($auction, $evaluation);
    }

    public function canAcceptBid(Auction $auction, int $additionalBits = 1): bool
    {
        if ($auction->closure_allowed) {
            return false;
        }

        if ($this->weeklyPrimary) {
            return true;
        }

        $nextBidEvaluation = $this->evaluateNextBid($auction, $additionalBits);

        return $nextBidEvaluation->marginPercent <= (float) ($auction->max_margin_percent ?? $this->defaultMaxMarginPercent);
    }

    public function evaluateFromMetrics(
        float $realCost,
        int $bitsConsumed,
        float $currentPrice,
        ?float $minMarginPercent = null,
        ?float $maxMarginPercent = null,
    ): MarginEvaluationResult {
        $minMargin = $minMarginPercent ?? $this->defaultMinMarginPercent;
        $maxMargin = $maxMarginPercent ?? $this->defaultMaxMarginPercent;

        if ($realCost <= 0) {
            return new MarginEvaluationResult(
                bitRevenue: 0,
                closingPriceRevenue: $currentPrice,
                totalRevenue: $currentPrice,
                realCost: $realCost,
                netProfit: 0,
                marginPercent: 0,
                canClose: false,
                shouldExtendTimer: true,
                reason: 'invalid_real_cost',
            );
        }

        $bitRevenue = $bitsConsumed * $this->bitValueEur;
        $totalRevenue = $bitRevenue + $currentPrice;
        $netProfit = $totalRevenue - $realCost;
        $marginPercent = ($netProfit / $realCost) * 100;

        $canClose = $marginPercent >= $minMargin && $marginPercent <= $maxMargin;
        $shouldExtendTimer = $marginPercent < $minMargin;

        $reason = match (true) {
            $marginPercent < $minMargin => 'margin_below_minimum',
            $marginPercent > $maxMargin => 'margin_above_maximum',
            default => 'margin_within_range',
        };

        return new MarginEvaluationResult(
            bitRevenue: $bitRevenue,
            closingPriceRevenue: $currentPrice,
            totalRevenue: $totalRevenue,
            realCost: $realCost,
            netProfit: $netProfit,
            marginPercent: $marginPercent,
            canClose: $canClose,
            shouldExtendTimer: $shouldExtendTimer,
            reason: $reason,
        );
    }

    /**
     * Determina si el temporizador puede expirar o debe aplicarse retención.
     */
    public function resolveTimerOnExpiry(Auction $auction): TimerResolution
    {
        $evaluation = $this->evaluate($auction);

        if ($evaluation->canClose) {
            return new TimerResolution(
                shouldEnd: true,
                extensionSeconds: 0,
                evaluation: $evaluation,
            );
        }

        if ($evaluation->shouldExtendTimer) {
            return new TimerResolution(
                shouldEnd: false,
                extensionSeconds: $this->retentionExtensionSeconds,
                evaluation: $evaluation,
            );
        }

        // Margen individual alto: cerramos para no inflar de más una sola subasta.
        return new TimerResolution(
            shouldEnd: true,
            extensionSeconds: 0,
            evaluation: $evaluation,
        );
    }

    public function syncClosureFlag(Auction $auction): MarginEvaluationResult
    {
        $evaluation = $this->evaluate($auction);
        $auction->forceFill(['closure_allowed' => $evaluation->canClose])->save();

        return $evaluation;
    }

    private function applyWeeklyClosureContext(
        Auction $auction,
        MarginEvaluationResult $evaluation,
    ): MarginEvaluationResult {
        if (! $this->weeklyPrimary || $evaluation->reason === 'invalid_real_cost') {
            return $evaluation;
        }

        $weeklyBalancer = app(WeeklyMarginBalancerService::class);
        $weeklyReport = $weeklyBalancer->buildWeeklyReport();

        if ($evaluation->canClose) {
            return $evaluation;
        }

        if ($weeklyBalancer->canAllowSubsidizedClose($weeklyReport, $auction, $evaluation)) {
            return new MarginEvaluationResult(
                bitRevenue: $evaluation->bitRevenue,
                closingPriceRevenue: $evaluation->closingPriceRevenue,
                totalRevenue: $evaluation->totalRevenue,
                realCost: $evaluation->realCost,
                netProfit: $evaluation->netProfit,
                marginPercent: $evaluation->marginPercent,
                canClose: true,
                shouldExtendTimer: false,
                reason: 'weekly_subsidy_close',
            );
        }

        $shouldExtend = $weeklyBalancer->shouldExtendForWeeklyTarget($weeklyReport, $auction, $evaluation);

        return new MarginEvaluationResult(
            bitRevenue: $evaluation->bitRevenue,
            closingPriceRevenue: $evaluation->closingPriceRevenue,
            totalRevenue: $evaluation->totalRevenue,
            realCost: $evaluation->realCost,
            netProfit: $evaluation->netProfit,
            marginPercent: $evaluation->marginPercent,
            canClose: false,
            shouldExtendTimer: $shouldExtend,
            reason: $shouldExtend ? 'weekly_margin_below_minimum' : $evaluation->reason,
        );
    }

    /**
     * Calcula el rango de precio de cierre válido para un margen objetivo.
     *
     * Ingresos = (bits × valor_bit) + precio_cierre
     * Margen% = (ingresos − costo_real) / costo_real × 100
     */
    public function calculateClosingPriceRange(
        float $realCost,
        int $bitsConsumed,
        ?float $minMarginPercent = null,
        ?float $maxMarginPercent = null,
    ): array {
        $minMargin = $minMarginPercent ?? $this->defaultMinMarginPercent;
        $maxMargin = $maxMarginPercent ?? $this->defaultMaxMarginPercent;
        $bitRevenue = $bitsConsumed * $this->bitValueEur;

        if ($realCost <= 0) {
            return [
                'bit_value_eur' => $this->bitValueEur,
                'bits_consumed' => $bitsConsumed,
                'bit_revenue' => 0,
                'real_cost' => $realCost,
                'min_margin_percent' => $minMargin,
                'max_margin_percent' => $maxMargin,
                'closing_price_floor' => 0,
                'closing_price_ceiling' => 0,
                'is_range_valid' => false,
                'formula' => 'Ingresos = (Bits × valor_bit) + precio de cierre. Margen = (Ingresos − costo real) / costo real × 100',
            ];
        }

        $minTotalRevenue = $realCost * (1 + $minMargin / 100);
        $maxTotalRevenue = $realCost * (1 + $maxMargin / 100);
        $floor = max(0, $minTotalRevenue - $bitRevenue);
        $ceiling = max(0, $maxTotalRevenue - $bitRevenue);

        return [
            'bit_value_eur' => $this->bitValueEur,
            'bits_consumed' => $bitsConsumed,
            'bit_revenue' => round($bitRevenue, 2),
            'real_cost' => round($realCost, 2),
            'min_margin_percent' => $minMargin,
            'max_margin_percent' => $maxMargin,
            'min_total_revenue' => round($minTotalRevenue, 2),
            'max_total_revenue' => round($maxTotalRevenue, 2),
            'closing_price_floor' => round($floor, 2),
            'closing_price_ceiling' => round($ceiling, 2),
            'is_range_valid' => $floor <= $ceiling,
            'formula' => 'Ingresos = (Bits × valor_bit) + precio de cierre. Margen = (Ingresos − costo real) / costo real × 100',
        ];
    }

    public function calculateRecommendedAuctionStrategy(
        float $realCost,
        int $bitsConsumed = 0,
        ?float $minMarginPercent = null,
        ?float $maxMarginPercent = null,
        ?float $retailValue = null,
    ): array {
        $weeklyMin = $this->weeklyMinMarginPercent;
        $weeklyMax = $this->weeklyMaxMarginPercent;
        $weeklyTarget = ($weeklyMin + $weeklyMax) / 2;

        if ($realCost <= 0) {
            return [
                'recommended_customer_price_target' => 0,
                'recommended_bits_target' => 0,
                'recommended_bits_min' => 0,
                'recommended_bits_max' => 0,
                'recommended_retail_value' => 0,
                'recommended_starting_price' => 0,
                'product_role' => 'unknown',
                'suggested_bid_increment' => 0.01,
                'strategy_note' => 'Define primero un coste real válido para calcular una estrategia.',
            ];
        }

        $isMarginCarrier = $realCost >= $this->marginCarrierCostThreshold;
        $productRole = $isMarginCarrier ? 'margin_carrier' : 'attractor';

        $targetClosingPrice = match (true) {
            $realCost >= 1500 => $this->clamp(round($realCost * 0.01, 2), 10.0, 99.0),
            $realCost >= 500 => $this->clamp(round($realCost * 0.02, 2), 5.0, 49.99),
            $realCost >= 150 => $this->clamp(round($realCost * 0.03, 2), 3.0, 29.99),
            default => $this->clamp(round($realCost * 0.05, 2), 1.0, 19.99),
        };

        $suggestedRetailValue = $retailValue ?? $this->roundDisplayPrice(max(
            $realCost * $this->retailMultiplier,
            $targetClosingPrice * 15
        ));

        $recommendedStartingPrice = $this->roundDisplayPrice(max(
            $suggestedRetailValue * 0.08,
            $targetClosingPrice * 2
        ));

        if ($isMarginCarrier) {
            $targetTotalRevenue = $realCost * (1 + ($weeklyTarget / 100));
            $minTotalRevenue = $realCost * (1 + ($weeklyMin / 100));
            $maxTotalRevenue = $realCost * (1 + ($weeklyMax / 100));
        } else {
            $targetTotalRevenue = $realCost * 0.65;
            $minTotalRevenue = $realCost * 0.45;
            $maxTotalRevenue = $realCost * 0.85;
        }

        $recommendedBitsTarget = max(0, (int) ceil(($targetTotalRevenue - $targetClosingPrice) / $this->bitValueEur));
        $recommendedBitsMin = max(0, (int) ceil(($minTotalRevenue - $targetClosingPrice) / $this->bitValueEur));
        $recommendedBitsMax = max($recommendedBitsMin, (int) floor(($maxTotalRevenue - $targetClosingPrice) / $this->bitValueEur));

        $suggestedBidIncrement = $this->bitValueEur;

        $strategyNote = $isMarginCarrier
            ? 'Producto generador de margen: aquí entra la pasta de la semana. Objetivo global 17–25%.'
            : 'Producto gancho: puede cerrar barato si la semana ya va bien. El margen global debe quedar 17–25%.';

        $targetProjection = $this->projectFromPlan($realCost, $recommendedBitsTarget, $targetClosingPrice);
        $minProjection = $this->projectFromPlan($realCost, $recommendedBitsMin, $targetClosingPrice);
        $maxProjection = $this->projectFromPlan($realCost, $recommendedBitsMax, $targetClosingPrice);

        return [
            'recommended_customer_price_target' => round($targetClosingPrice, 2),
            'recommended_bits_target' => $recommendedBitsTarget,
            'recommended_bits_min' => $recommendedBitsMin,
            'recommended_bits_max' => $recommendedBitsMax,
            'recommended_retail_value' => $suggestedRetailValue,
            'recommended_starting_price' => $recommendedStartingPrice,
            'product_role' => $productRole,
            'suggested_bid_increment' => $suggestedBidIncrement,
            'strategy_note' => $strategyNote,
            'weekly_margin_target_min' => $weeklyMin,
            'weekly_margin_target_max' => $weeklyMax,
            'current_estimated_bits_viable' => $bitsConsumed >= $recommendedBitsMin && $bitsConsumed <= $recommendedBitsMax,
            'projected_revenue' => $targetProjection['total_revenue'],
            'projected_profit' => $targetProjection['net_profit'],
            'projected_margin_percent' => $targetProjection['margin_percent'],
            'projected_profit_min' => $minProjection['net_profit'],
            'projected_profit_max' => $maxProjection['net_profit'],
            'projected_margin_min' => $minProjection['margin_percent'],
            'projected_margin_max' => $maxProjection['margin_percent'],
        ];
    }

    /**
     * @return array{
     *     bit_revenue: float,
     *     closing_price: float,
     *     total_revenue: float,
     *     net_profit: float,
     *     margin_percent: float
     * }
     */
    public function projectFromPlan(float $realCost, int $bitsConsumed, float $closingPrice): array
    {
        $bitRevenue = $bitsConsumed * $this->bitValueEur;
        $totalRevenue = $bitRevenue + $closingPrice;
        $netProfit = $totalRevenue - $realCost;
        $marginPercent = $realCost > 0 ? ($netProfit / $realCost) * 100 : 0.0;

        return [
            'bit_revenue' => round($bitRevenue, 2),
            'closing_price' => round($closingPrice, 2),
            'total_revenue' => round($totalRevenue, 2),
            'net_profit' => round($netProfit, 2),
            'margin_percent' => round($marginPercent, 2),
        ];
    }

    public function buildProductStrategy(float $realCost, int $estimatedBits = 0, ?float $retailValue = null): array
    {
        $strategy = $this->calculateRecommendedAuctionStrategy(
            realCost: $realCost,
            bitsConsumed: $estimatedBits,
            retailValue: $retailValue,
        );

        $preview = $this->calculateClosingPriceRange(
            realCost: $realCost,
            bitsConsumed: $estimatedBits,
            minMarginPercent: $this->weeklyMinMarginPercent,
            maxMarginPercent: $this->weeklyMaxMarginPercent,
        );

        return [
            'estimated_bits' => $estimatedBits,
            'strategy' => $strategy,
            'closing_preview' => $preview,
            'saved_at' => now()->toIso8601String(),
        ];
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    private function roundDisplayPrice(float $value): float
    {
        $rounded = ceil($value / 10) * 10;

        return round(max(9.99, $rounded - 0.01), 2);
    }
}

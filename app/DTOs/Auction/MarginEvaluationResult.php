<?php

declare(strict_types=1);

namespace App\DTOs\Auction;

final readonly class MarginEvaluationResult
{
    public function __construct(
        public float $bitRevenue,
        public float $closingPriceRevenue,
        public float $totalRevenue,
        public float $realCost,
        public float $netProfit,
        public float $marginPercent,
        public bool $canClose,
        public bool $shouldExtendTimer,
        public ?string $reason = null,
    ) {}

    public function toArray(): array
    {
        return [
            'bit_revenue' => round($this->bitRevenue, 2),
            'closing_price_revenue' => round($this->closingPriceRevenue, 2),
            'total_revenue' => round($this->totalRevenue, 2),
            'real_cost' => round($this->realCost, 2),
            'net_profit' => round($this->netProfit, 2),
            'margin_percent' => round($this->marginPercent, 4),
            'can_close' => $this->canClose,
            'should_extend_timer' => $this->shouldExtendTimer,
            'reason' => $this->reason,
        ];
    }
}

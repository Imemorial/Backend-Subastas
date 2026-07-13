<?php

declare(strict_types=1);

namespace App\DTOs\Auction;

final readonly class WeeklyMarginReport
{
    public function __construct(
        public int $isoYear,
        public int $isoWeek,
        public float $marginPercent,
        public float $targetMinMargin,
        public float $targetMaxMargin,
        public bool $isWithinTarget,
        public float $adjustmentFactor,
        public array $metrics,
    ) {}

    public function toArray(): array
    {
        return [
            'iso_year' => $this->isoYear,
            'iso_week' => $this->isoWeek,
            'margin_percent' => round($this->marginPercent, 4),
            'target_min_margin' => $this->targetMinMargin,
            'target_max_margin' => $this->targetMaxMargin,
            'is_within_target' => $this->isWithinTarget,
            'adjustment_factor' => round($this->adjustmentFactor, 4),
            'metrics' => $this->metrics,
        ];
    }
}

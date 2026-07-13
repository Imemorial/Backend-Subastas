<?php

declare(strict_types=1);

namespace App\Services\Auction;

use App\DTOs\Auction\MarginEvaluationResult;

final readonly class TimerResolution
{
    public function __construct(
        public bool $shouldEnd,
        public int $extensionSeconds,
        public MarginEvaluationResult $evaluation,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Services\Auction;

use App\Enums\AuctionStatus;
use App\Models\Auction;

use App\Services\Auction\AuctionBidService;
use App\Services\Auction\AuctionManagementService;
use App\Services\Auction\WeeklyMarginBalancerService;
use Illuminate\Support\Facades\Cache;

final class AuctionTimerProcessor
{
    public function __construct(
        private readonly AuctionBidService $bidService,
        private readonly AuctionManagementService $auctionManagementService,
        private readonly WeeklyMarginBalancerService $weeklyMarginBalancer,
    ) {}

    /** Ejecuta como máximo una vez cada 4 segundos (p. ej. desde /home). */
    public function processMaybe(): void
    {
        if (! Cache::add('auction-timers:lock', 1, 4)) {
            return;
        }

        $this->process();
    }

    public function process(): void
    {
        Auction::query()
            ->where('status', AuctionStatus::Scheduled)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->each(fn (Auction $auction) => $this->auctionManagementService->activate($auction));

        Auction::query()
            ->where('status', AuctionStatus::Active)
            ->where('ends_at', '<=', now())
            ->each(fn (Auction $auction) => $this->bidService->handleTimerExpiry($auction));

        if (now()->second < 5) {
            $this->weeklyMarginBalancer->balanceActiveAuctions();
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Services\Auction\AuctionBidService;
use App\Services\Auction\AuctionManagementService;
use App\Services\Auction\WeeklyMarginBalancerService;
use Illuminate\Console\Command;

final class ProcessAuctionTimersCommand extends Command
{
    protected $signature = 'auctions:process-timers';

    protected $description = 'Procesa subastas activas cuyo temporizador ha expirado';

    public function handle(
        AuctionBidService $bidService,
        AuctionManagementService $auctionManagementService,
        WeeklyMarginBalancerService $weeklyMarginBalancer,
    ): int
    {
        Auction::query()
            ->where('status', AuctionStatus::Scheduled)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->each(fn (Auction $auction) => $auctionManagementService->activate($auction));

        Auction::query()
            ->where('status', AuctionStatus::Active)
            ->where('ends_at', '<=', now())
            ->each(fn (Auction $auction) => $bidService->handleTimerExpiry($auction));

        if (now()->second < 5) {
            $weeklyMarginBalancer->balanceActiveAuctions();
        }

        $this->info('Temporizadores procesados.');

        return self::SUCCESS;
    }
}

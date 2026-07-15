<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Auction\AuctionTimerProcessor;
use Illuminate\Console\Command;

final class ProcessAuctionTimersCommand extends Command
{
    protected $signature = 'auctions:process-timers';

    protected $description = 'Procesa subastas activas cuyo temporizador ha expirado';

    public function handle(AuctionTimerProcessor $timerProcessor): int
    {
        $timerProcessor->process();

        $this->info('Temporizadores procesados.');

        return self::SUCCESS;
    }
}

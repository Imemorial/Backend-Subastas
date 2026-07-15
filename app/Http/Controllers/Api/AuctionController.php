<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AuctionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuctionResource;
use App\Models\Auction;
use App\Services\Auction\AuctionTimerProcessor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AuctionController extends Controller
{
    public function __construct(
        private readonly AuctionTimerProcessor $timerProcessor,
    ) {}

    public function upcoming(): AnonymousResourceCollection
    {
        $this->timerProcessor->process();

        $auctions = Auction::query()
            ->with('product.images')
            ->whereHas('product')
            ->where('status', AuctionStatus::Scheduled)
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        return AuctionResource::collection($auctions);
    }

    public function recentWins(): AnonymousResourceCollection
    {
        $auctions = Auction::query()
            ->with(['product.images', 'winner'])
            ->whereHas('product')
            ->where('status', AuctionStatus::Ended)
            ->whereNotNull('winner_user_id')
            ->orderByDesc('ended_at')
            ->limit(4)
            ->get();

        return AuctionResource::collection($auctions);
    }

    public function index(): AnonymousResourceCollection
    {
        $this->timerProcessor->process();

        $auctions = Auction::query()
            ->with(['product.images', 'bids' => fn ($q) => $q->with('user')->latest('bid_at')->limit(1)])
            ->whereHas('product')
            ->where('status', AuctionStatus::Active)
            ->orderBy('ends_at')
            ->get();

        return AuctionResource::collection($auctions);
    }

    public function show(Auction $auction): AuctionResource
    {
        $auction->load([
            'product.images',
            'bids' => fn ($q) => $q->with('user')->latest('bid_at')->limit(20),
        ]);

        return new AuctionResource($auction);
    }
}

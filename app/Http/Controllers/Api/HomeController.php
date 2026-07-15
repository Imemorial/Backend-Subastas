<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AuctionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuctionResource;
use App\Http\Resources\WinnerShowcaseResource;
use App\Models\Auction;
use App\Models\WinnerShowcase;
use Illuminate\Http\JsonResponse;

final class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        $active = Auction::query()
            ->with([
                'product.images' => fn ($query) => $query->limit(1),
                'bids' => fn ($query) => $query->with('user:id,name')->latest('bid_at')->limit(1),
            ])
            ->whereHas('product')
            ->where('status', AuctionStatus::Active)
            ->orderBy('ends_at')
            ->get();

        $upcoming = Auction::query()
            ->with(['product.images' => fn ($query) => $query->limit(1)])
            ->whereHas('product')
            ->where('status', AuctionStatus::Scheduled)
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        $showcases = WinnerShowcase::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $recentWinners = $showcases->isEmpty()
            ? Auction::query()
                ->with(['product.images' => fn ($query) => $query->limit(1), 'winner:id,name'])
                ->whereHas('product')
                ->where('status', AuctionStatus::Ended)
                ->whereNotNull('winner_user_id')
                ->orderByDesc('ended_at')
                ->limit(4)
                ->get()
            : collect();

        return response()->json([
            'active' => ['data' => AuctionResource::collection($active)->resolve()],
            'upcoming' => ['data' => AuctionResource::collection($upcoming)->resolve()],
            'winners' => [
                'data' => $showcases->isNotEmpty()
                    ? WinnerShowcaseResource::collection($showcases)->resolve()
                    : AuctionResource::collection($recentWinners)->resolve(),
            ],
            'winners_type' => $showcases->isNotEmpty() ? 'showcase' : 'recent',
        ]);
    }
}

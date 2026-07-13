<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BidResource;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class UserDashboardController extends Controller
{
    public function bids(Request $request): AnonymousResourceCollection
    {
        $bids = $request->user()
            ->bids()
            ->with(['auction.product'])
            ->latest('bid_at')
            ->paginate(20);

        return BidResource::collection($bids);
    }

    public function transactions(Request $request): AnonymousResourceCollection
    {
        $transactions = $request->user()
            ->transactions()
            ->latest()
            ->paginate(20);

        return TransactionResource::collection($transactions);
    }
}

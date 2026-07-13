<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBidRequest;
use App\Http\Resources\BidResource;
use App\Models\Auction;
use App\Services\Auction\AuctionBidService;
use Illuminate\Http\JsonResponse;

final class BidController extends Controller
{
    public function __construct(
        private readonly AuctionBidService $bidService,
    ) {}

    public function store(StoreBidRequest $request, Auction $auction): JsonResponse
    {
        $bitsCount = (int) $request->input('bits_count', 1);
        $bid = $this->bidService->placeBid($auction, $request->user(), $bitsCount);

        return response()->json([
            'message' => 'Puja realizada correctamente.',
            'bid' => new BidResource($bid->load('user')),
            'bit_balance' => $request->user()->fresh()->bit_balance,
        ], 201);
    }
}

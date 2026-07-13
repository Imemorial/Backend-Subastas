<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarginPreviewRequest;
use App\Http\Requests\Admin\StoreAuctionRequest;
use App\Http\Resources\AuctionResource;
use App\Models\Auction;
use App\Models\Product;
use App\Services\Auction\AuctionManagementService;
use App\Services\Auction\AuctionMarginService;
use App\Services\Auction\WeeklyMarginBalancerService;
use App\Services\Auction\WeeklySchedulePlannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AuctionAdminController extends Controller
{
    public function __construct(
        private readonly AuctionManagementService $auctionService,
        private readonly WeeklyMarginBalancerService $marginBalancer,
        private readonly AuctionMarginService $marginService,
        private readonly WeeklySchedulePlannerService $schedulePlanner,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $auctions = Auction::query()
            ->with('product')
            ->latest()
            ->paginate(20);

        return AuctionResource::collection($auctions);
    }

    public function store(StoreAuctionRequest $request): JsonResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));

        $auction = $this->auctionService->createFromProduct($product, $request->validated());

        return response()->json([
            'message' => 'Subasta creada correctamente.',
            'auction' => new AuctionResource($auction),
        ], 201);
    }

    public function activate(Auction $auction): JsonResponse
    {
        $auction = $this->auctionService->activate($auction);

        return response()->json([
            'message' => 'Subasta activada.',
            'auction' => new AuctionResource($auction),
        ]);
    }

    public function pause(Auction $auction): JsonResponse
    {
        $auction = $this->auctionService->pause($auction);

        return response()->json([
            'message' => 'Subasta pausada.',
            'auction' => new AuctionResource($auction),
        ]);
    }

    public function resume(Auction $auction): JsonResponse
    {
        $auction = $this->auctionService->resume($auction);

        return response()->json([
            'message' => 'Subasta reanudada.',
            'auction' => new AuctionResource($auction),
        ]);
    }

    public function weeklyMargin(): JsonResponse
    {
        $report = $this->marginBalancer->buildWeeklyReport();

        return response()->json($report->toArray());
    }

    public function weeklySchedule(): JsonResponse
    {
        return response()->json($this->schedulePlanner->buildPlan());
    }

    public function marginPreview(MarginPreviewRequest $request): JsonResponse
    {
        $realCost = (float) $request->input('real_cost');
        $bitsConsumed = (int) $request->input('bits_consumed', 0);
        $minMargin = $request->has('min_margin_percent')
            ? (float) $request->input('min_margin_percent')
            : null;
        $maxMargin = $request->has('max_margin_percent')
            ? (float) $request->input('max_margin_percent')
            : null;

        $preview = $this->marginService->calculateClosingPriceRange(
            realCost: $realCost,
            bitsConsumed: $bitsConsumed,
            minMarginPercent: $minMargin ?? (float) config('auction.margin.weekly_min_percent'),
            maxMarginPercent: $maxMargin ?? (float) config('auction.margin.weekly_max_percent'),
        );

        $strategy = $this->marginService->calculateRecommendedAuctionStrategy(
            realCost: $realCost,
            bitsConsumed: $bitsConsumed,
            minMarginPercent: $minMargin,
            maxMarginPercent: $maxMargin,
            retailValue: $request->has('retail_value') ? (float) $request->input('retail_value') : null,
        );

        return response()->json(array_merge($preview, [
            'strategy' => $strategy,
        ]));
    }

    public function auctionMargin(Auction $auction): JsonResponse
    {
        $auction->load('product');
        $evaluation = $this->marginService->evaluate($auction);
        $closingRange = $this->marginService->calculateClosingPriceRange(
            realCost: (float) $auction->product->real_cost,
            bitsConsumed: (int) $auction->bits_consumed,
            minMarginPercent: (float) $auction->min_margin_percent,
            maxMarginPercent: (float) $auction->max_margin_percent,
        );

        return response()->json([
            'evaluation' => $evaluation->toArray(),
            'closing_range' => $closingRange,
        ]);
    }

    public function balanceWeekly(): JsonResponse
    {
        $report = $this->marginBalancer->balanceActiveAuctions();

        return response()->json([
            'message' => 'Balance semanal aplicado a subastas activas.',
            'report' => $report->toArray(),
        ]);
    }
}

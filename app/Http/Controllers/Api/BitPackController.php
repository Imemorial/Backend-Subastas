<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseBitPackRequest;
use App\Http\Resources\BitPackResource;
use App\Http\Resources\TransactionResource;
use App\Models\BitPack;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class BitPackController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $packs = BitPack::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return BitPackResource::collection($packs);
    }

    public function purchase(PurchaseBitPackRequest $request): JsonResponse
    {
        $pack = BitPack::query()->findOrFail($request->integer('bit_pack_id'));
        $user = $request->user();

        $transaction = $this->walletService->purchaseBitPack(
            $user,
            $pack,
            $request->string('idempotency_key')->toString() ?: null,
        );

        return response()->json([
            'message' => 'Compra de Bits completada.',
            'transaction' => new TransactionResource($transaction),
            'bit_balance' => $user->fresh()->bit_balance,
        ], 201);
    }
}

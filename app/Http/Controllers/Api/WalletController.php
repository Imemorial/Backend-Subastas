<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function testRecharge(Request $request): JsonResponse
    {
        if (! app()->environment('local', 'testing')) {
            abort(404);
        }

        $user = $request->user();
        $transaction = $this->walletService->grantTestBits($user, 100);

        return response()->json([
            'message' => 'Recarga de prueba completada.',
            'bits_added' => 100,
            'bit_balance' => $user->fresh()->bit_balance,
            'transaction' => new TransactionResource($transaction),
        ]);
    }
}

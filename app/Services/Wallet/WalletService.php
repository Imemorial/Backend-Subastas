<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Enums\TransactionType;
use App\Models\BitPack;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payment\StripeMockGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WalletService
{
    public function __construct(
        private readonly StripeMockGateway $paymentGateway,
    ) {}

    public function purchaseBitPack(User $user, BitPack $pack, ?string $idempotencyKey = null): Transaction
    {
        if (! $pack->is_active) {
            throw ValidationException::withMessages([
                'bit_pack_id' => 'Este pack no está disponible.',
            ]);
        }

        if ($idempotencyKey !== null) {
            $existing = Transaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('user_id', $user->id)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($user, $pack, $idempotencyKey): Transaction {
            $payment = $this->paymentGateway->charge(
                amountEur: (float) $pack->price_eur,
                metadata: ['bit_pack_id' => $pack->id, 'user_id' => $user->id],
            );

            if (! $payment['success']) {
                throw ValidationException::withMessages([
                    'payment' => 'El pago no pudo procesarse.',
                ]);
            }

            $totalBits = $pack->bits_amount + $pack->bonus_bits;

            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'bit_pack_id' => $pack->id,
                'type' => TransactionType::BitPurchase,
                'status' => 'completed',
                'bits_delta' => $totalBits,
                'amount_eur' => $pack->price_eur,
                'payment_provider' => 'stripe_mock',
                'payment_intent_id' => $payment['payment_intent_id'],
                'idempotency_key' => $idempotencyKey,
                'completed_at' => now(),
            ]);

            $user->increment('bit_balance', $totalBits);

            return $transaction;
        });
    }

    public function grantTestBits(User $user, int $bits = 100): Transaction
    {
        return DB::transaction(function () use ($user, $bits): Transaction {
            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'type' => TransactionType::AdminAdjustment,
                'status' => 'completed',
                'bits_delta' => $bits,
                'amount_eur' => 0,
                'payment_provider' => 'test',
                'metadata' => ['source' => 'test_recharge'],
                'completed_at' => now(),
            ]);

            $user->increment('bit_balance', $bits);

            return $transaction;
        });
    }
}

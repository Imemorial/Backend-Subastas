<?php



declare(strict_types=1);



namespace App\Services\Auction;



use App\DTOs\Auction\MarginEvaluationResult;

use App\Enums\AuctionStatus;

use App\Enums\TransactionType;

use App\Events\AuctionBidPlaced;

use App\Events\AuctionTimerUpdated;

use App\Models\Auction;

use App\Models\Bid;

use App\Models\Transaction;

use App\Models\User;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;



final class AuctionBidService

{

    public function __construct(

        private readonly AuctionMarginService $marginService,

    ) {}



    public function placeBid(Auction $auction, User $user, int $bitsCount = 1): Bid

    {

        if ($bitsCount < 1) {

            throw ValidationException::withMessages([

                'bits_count' => 'Debes pujar al menos 1 Bit.',

            ]);

        }



        if ($auction->status !== AuctionStatus::Active) {

            throw ValidationException::withMessages([

                'auction' => 'La subasta no está activa.',

            ]);

        }



        if ($user->bit_balance < $bitsCount) {

            throw ValidationException::withMessages([

                'balance' => 'Saldo insuficiente de Bits.',

            ]);

        }



        return DB::transaction(function () use ($auction, $user, $bitsCount): Bid {

            $auction = Auction::query()->lockForUpdate()->findOrFail($auction->id);

            $user = User::query()->lockForUpdate()->findOrFail($user->id);



            if ($user->bit_balance < $bitsCount) {

                throw ValidationException::withMessages([

                    'balance' => 'Saldo insuficiente de Bits.',

                ]);

            }



            if ($auction->closure_allowed) {

                throw ValidationException::withMessages([

                    'auction' => 'La subasta ya está en ventana de cierre y no acepta más pujas.',

                ]);

            }



            $priceStep = $bitsCount * (float) $auction->bid_increment;

            $newPrice = (float) $auction->current_price + $priceStep;

            $evaluation = $this->marginService->evaluateNextBid($auction, $bitsCount);



            if (! $this->marginService->canAcceptBid($auction, $bitsCount)) {

                throw ValidationException::withMessages([

                    'auction' => 'La subasta ya está en ventana de cierre y no acepta más pujas.',

                ]);

            }



            Bid::query()

                ->where('auction_id', $auction->id)

                ->where('is_winning', true)

                ->update(['is_winning' => false]);



            $bitValue = (float) config('auction.bit_value_eur', 0.20);



            $user->decrement('bit_balance', $bitsCount);

            $auction->increment('bits_consumed', $bitsCount);

            $auction->increment('total_bids');



            Transaction::query()->create([

                'user_id' => $user->id,

                'type' => TransactionType::BidDebit,

                'status' => 'completed',

                'bits_delta' => -$bitsCount,

                'amount_eur' => round($bitsCount * $bitValue, 2),

                'reference_type' => Auction::class,

                'reference_id' => $auction->id,

                'completed_at' => now(),

            ]);



            $bid = Bid::query()->create([

                'auction_id' => $auction->id,

                'user_id' => $user->id,

                'amount' => $newPrice,

                'bits_spent' => $bitsCount,

                'is_winning' => true,

                'margin_percent_at_bid' => $evaluation->marginPercent,

                'closure_was_allowed' => $evaluation->canClose,

                'bid_at' => now(),

            ]);



            $remainingSeconds = (int) $auction->timer_extension_seconds;



            $auction->forceFill([

                'current_price' => $newPrice,

                'remaining_seconds' => $remainingSeconds,

                'ends_at' => now()->addSeconds($remainingSeconds),

                'closure_allowed' => $evaluation->canClose,

            ])->save();



            event(new AuctionBidPlaced($bid->load(['user', 'auction'])));

            event(new AuctionTimerUpdated($auction->fresh()));



            return $bid;

        });

    }



    public function handleTimerExpiry(Auction $auction): void

    {

        DB::transaction(function () use ($auction): void {

            $auction = Auction::query()->lockForUpdate()->findOrFail($auction->id);



            if ($auction->status !== AuctionStatus::Active) {

                return;

            }



            $resolution = $this->marginService->resolveTimerOnExpiry($auction);



            if ($resolution->shouldEnd) {

                $winningBid = Bid::query()

                    ->where('auction_id', $auction->id)

                    ->where('is_winning', true)

                    ->first();



                $auction->forceFill([

                    'status' => AuctionStatus::Ended,

                    'ended_at' => now(),

                    'closure_allowed' => $resolution->evaluation->canClose,

                    'winner_user_id' => $winningBid?->user_id,

                    'remaining_seconds' => 0,

                ])->save();

                WeeklyMarginBalancerService::forgetWeeklyReportCache();

                return;

            }



            $extension = $resolution->extensionSeconds;

            $auction->forceFill([

                'remaining_seconds' => $extension,

                'ends_at' => now()->addSeconds($extension),

                'closure_allowed' => false,

            ])->save();



            event(new AuctionTimerUpdated($auction->fresh()));

        });

    }

}



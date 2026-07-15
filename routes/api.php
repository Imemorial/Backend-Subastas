<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\AuctionAdminController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\WinnerShowcaseAdminController;
use App\Http\Controllers\Api\AuctionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BidController;
use App\Http\Controllers\Api\BitPackController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserDashboardController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WinnerShowcaseController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/auctions/upcoming', [AuctionController::class, 'upcoming']);
    Route::get('/auctions/recent-wins', [AuctionController::class, 'recentWins']);
    Route::get('/auctions', [AuctionController::class, 'index']);
    Route::get('/auctions/{auction}', [AuctionController::class, 'show']);
    Route::get('/bit-packs', [BitPackController::class, 'index']);
    Route::get('/winner-showcases', [WinnerShowcaseController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::post('/auctions/{auction}/bids', [BidController::class, 'store']);
        Route::post('/bit-packs/purchase', [BitPackController::class, 'purchase']);
        Route::post('/wallet/test-recharge', [WalletController::class, 'testRecharge']);

        Route::get('/me/bids', [UserDashboardController::class, 'bids']);
        Route::get('/me/transactions', [UserDashboardController::class, 'transactions']);
        Route::patch('/me/profile', [ProfileController::class, 'update']);
        Route::patch('/me/email', [ProfileController::class, 'updateEmail']);
        Route::patch('/me/password', [ProfileController::class, 'updatePassword']);

        Route::middleware(EnsureUserIsAdmin::class)->prefix('admin')->group(function (): void {
            Route::apiResource('products', ProductController::class);
            Route::post('products/{product}', [ProductController::class, 'update']);
            Route::get('winner-showcases', [WinnerShowcaseAdminController::class, 'index']);
            Route::post('winner-showcases', [WinnerShowcaseAdminController::class, 'store']);
            Route::post('winner-showcases/{winnerShowcase}', [WinnerShowcaseAdminController::class, 'update']);
            Route::patch('winner-showcases/{winnerShowcase}', [WinnerShowcaseAdminController::class, 'update']);
            Route::delete('winner-showcases/{winnerShowcase}', [WinnerShowcaseAdminController::class, 'destroy']);
            Route::get('auctions', [AuctionAdminController::class, 'index']);
            Route::post('auctions', [AuctionAdminController::class, 'store']);
            Route::post('auctions/{auction}/activate', [AuctionAdminController::class, 'activate']);
            Route::post('auctions/{auction}/pause', [AuctionAdminController::class, 'pause']);
            Route::post('auctions/{auction}/resume', [AuctionAdminController::class, 'resume']);
            Route::get('analytics/weekly-margin', [AuctionAdminController::class, 'weeklyMargin']);
            Route::get('analytics/weekly-schedule', [AuctionAdminController::class, 'weeklySchedule']);
            Route::post('analytics/margin-preview', [AuctionAdminController::class, 'marginPreview']);
            Route::post('analytics/balance-weekly', [AuctionAdminController::class, 'balanceWeekly']);
            Route::get('auctions/{auction}/margin', [AuctionAdminController::class, 'auctionMargin']);
        });
    });
});

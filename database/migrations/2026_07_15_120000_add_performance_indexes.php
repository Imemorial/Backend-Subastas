<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table): void {
            $table->index(['status', 'scheduled_at']);
            $table->index(['status', 'ended_at']);
        });

        Schema::table('bids', function (Blueprint $table): void {
            $table->index(['auction_id', 'bid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table): void {
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropIndex(['status', 'ended_at']);
        });

        Schema::table('bids', function (Blueprint $table): void {
            $table->dropIndex(['auction_id', 'bid_at']);
        });
    }
};

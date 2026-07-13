<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $bitValue = (float) config('auction.bit_value_eur', 0.20);

        DB::table('auctions')->update(['bid_increment' => $bitValue]);

        Schema::table('auctions', function (Blueprint $table) use ($bitValue): void {
            $table->decimal('bid_increment', 8, 4)->default($bitValue)->change();
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table): void {
            $table->decimal('bid_increment', 8, 4)->default(0.01)->change();
        });
    }
};

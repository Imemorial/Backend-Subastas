<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->unsignedTinyInteger('bits_spent')->default(1);
            $table->boolean('is_winning')->default(true);
            $table->decimal('margin_percent_at_bid', 8, 4)->nullable();
            $table->boolean('closure_was_allowed')->default(false);

            $table->timestamp('bid_at')->useCurrent();
            $table->timestamps();

            $table->index(['auction_id', 'created_at']);
            $table->index(['auction_id', 'is_winning']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};

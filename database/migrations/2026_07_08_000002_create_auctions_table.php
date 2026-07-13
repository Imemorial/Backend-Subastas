<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('starting_price', 12, 2)->default(0.00);
            $table->decimal('current_price', 12, 2)->default(0.00);
            $table->decimal('bid_increment', 8, 4)->default(0.01);
            $table->unsignedSmallInteger('initial_timer_seconds')->default(10);
            $table->unsignedSmallInteger('timer_extension_seconds')->default(10);
            $table->unsignedSmallInteger('remaining_seconds')->default(10);

            $table->unsignedInteger('total_bids')->default(0);
            $table->unsignedInteger('bits_consumed')->default(0);

            $table->decimal('min_margin_percent', 5, 2)->default(17.00);
            $table->decimal('max_margin_percent', 5, 2)->default(25.00);
            $table->boolean('closure_allowed')->default(false);

            $table->enum('status', [
                'draft',
                'scheduled',
                'active',
                'paused',
                'ended',
                'cancelled',
            ])->default('draft');

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'ends_at']);
            $table->index(['status', 'started_at']);
            $table->index('closure_allowed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};

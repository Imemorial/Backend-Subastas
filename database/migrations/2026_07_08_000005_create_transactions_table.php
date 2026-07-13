<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bit_pack_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', [
                'bit_purchase',
                'bid_debit',
                'product_payment',
                'refund',
                'admin_adjustment',
            ]);

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
            ])->default('pending');

            $table->integer('bits_delta')->default(0);
            $table->decimal('amount_eur', 12, 2)->default(0.00);
            $table->string('currency', 3)->default('EUR');

            $table->nullableMorphs('reference');
            $table->string('payment_provider', 32)->nullable();
            $table->string('payment_intent_id')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();

            $table->json('metadata')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['type', 'completed_at']);
        });

        Schema::create('weekly_margin_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('iso_year');
            $table->unsignedTinyInteger('iso_week');
            $table->date('week_start');
            $table->date('week_end');

            $table->unsignedInteger('auctions_ended')->default(0);
            $table->unsignedInteger('total_bits_consumed')->default(0);
            $table->decimal('total_bit_revenue', 14, 2)->default(0.00);
            $table->decimal('total_closing_prices', 14, 2)->default(0.00);
            $table->decimal('total_real_cost', 14, 2)->default(0.00);
            $table->decimal('total_revenue', 14, 2)->default(0.00);
            $table->decimal('margin_percent', 8, 4)->default(0.0000);

            $table->decimal('target_min_margin', 5, 2)->default(17.00);
            $table->decimal('target_max_margin', 5, 2)->default(20.00);
            $table->json('balancer_state')->nullable();

            $table->timestamps();

            $table->unique(['iso_year', 'iso_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_margin_snapshots');
        Schema::dropIfExists('transactions');
    }
};

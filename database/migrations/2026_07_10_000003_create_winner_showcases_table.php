<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('winner_showcases', function (Blueprint $table): void {
            $table->id();
            $table->string('winner_name');
            $table->string('product_name');
            $table->string('short_description', 280)->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('final_price', 12, 2)->default(0);
            $table->decimal('retail_value', 12, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('winner_showcases');
    }
};

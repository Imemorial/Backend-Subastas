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
            $table->json('metadata')->nullable()->after('ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });
    }
};

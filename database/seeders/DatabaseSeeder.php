<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            BitPackSeeder::class,
            WinnerShowcaseSeeder::class,
        ]);

        if (filter_var(env('DEMO_SEED', false), FILTER_VALIDATE_BOOL)) {
            $this->call(DemoAuctionSeeder::class);
        }
    }
}

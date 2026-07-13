<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@bitsauction.test'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'bit_balance' => 0,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'cliente@bitsauction.test'],
            [
                'name' => 'Cliente Demo',
                'password' => Hash::make('password'),
                'role' => UserRole::Client,
                'bit_balance' => 150,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}

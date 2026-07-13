<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Client = 'client';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isClient(): bool
    {
        return $this === self::Client;
    }
}

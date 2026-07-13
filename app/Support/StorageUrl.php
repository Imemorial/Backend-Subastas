<?php

declare(strict_types=1);

namespace App\Support;

final class StorageUrl
{
    public static function publicAsset(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $baseUrl = rtrim((string) config('app.url'), '/');

        return $baseUrl.'/storage/'.ltrim($path, '/');
    }
}

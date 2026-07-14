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

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        $relative = '/storage/'.$normalized;
        $baseUrl = self::baseUrl();

        return $baseUrl !== '' ? $baseUrl.$relative : $relative;
    }

    private static function baseUrl(): string
    {
        if (! app()->runningInConsole() && request()) {
            return rtrim(request()->getSchemeAndHttpHost(), '/');
        }

        return rtrim((string) config('app.url'), '/');
    }
}

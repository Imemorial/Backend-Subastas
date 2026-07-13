<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WinnerShowcaseResource;
use App\Models\WinnerShowcase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class WinnerShowcaseController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $showcases = WinnerShowcase::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        return WinnerShowcaseResource::collection($showcases);
    }
}

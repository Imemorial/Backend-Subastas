<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\StorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WinnerShowcase */
final class WinnerShowcaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $retail = (float) $this->retail_value;
        $final = (float) $this->final_price;
        $discount = $retail > 0 ? (int) round((1 - $final / $retail) * 100) : 0;

        return [
            'id' => $this->id,
            'winner_name' => $this->winner_name,
            'product_name' => $this->product_name,
            'short_description' => $this->short_description,
            'image_url' => StorageUrl::publicAsset($this->image_path),
            'final_price' => $final,
            'retail_value' => $retail,
            'discount_percent' => max(0, $discount),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\StorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imagePaths = $this->relationLoaded('images')
            ? $this->images->pluck('path')->all()
            : array_filter([$this->image_path]);

        $imageUrls = array_values(array_filter(
            array_map(static fn (string $path): ?string => StorageUrl::publicAsset($path), $imagePaths),
        ));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $imageUrls[0] ?? StorageUrl::publicAsset($this->image_path),
            'image_urls' => $imageUrls,
            'real_cost' => (float) $this->real_cost,
            'retail_value' => $this->retail_value !== null ? (float) $this->retail_value : null,
            'sku' => $this->sku,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Models\Product;
use App\Services\Auction\AuctionMarginService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class ProductService
{
    public function __construct(
        private readonly AuctionMarginService $marginService,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $images
     */
    public function create(array $data, array $images = []): Product
    {
        $slug = Str::slug($data['name']);
        $baseSlug = $slug;
        $counter = 1;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        $estimatedBits = (int) ($data['estimated_bits'] ?? 0);
        $realCost = (float) $data['real_cost'];
        $retailValue = isset($data['retail_value']) ? (float) $data['retail_value'] : null;

        $metadata = $this->marginService->buildProductStrategy(
            realCost: $realCost,
            estimatedBits: $estimatedBits,
            retailValue: $retailValue,
        );

        $product = Product::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'real_cost' => $realCost,
            'retail_value' => $retailValue,
            'sku' => $data['sku'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'metadata' => $metadata,
        ]);

        $this->storeImages($product, $images);

        return $product->fresh(['images']);
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    public function update(Product $product, array $data, array $images = []): Product
    {
        $realCost = isset($data['real_cost']) ? (float) $data['real_cost'] : (float) $product->real_cost;
        $retailValue = array_key_exists('retail_value', $data)
            ? ($data['retail_value'] !== null ? (float) $data['retail_value'] : null)
            : ($product->retail_value !== null ? (float) $product->retail_value : null);
        $estimatedBits = (int) ($data['estimated_bits'] ?? ($product->metadata['estimated_bits'] ?? 0));

        $metadata = $product->metadata ?? [];
        if (isset($data['real_cost']) || isset($data['retail_value']) || isset($data['estimated_bits'])) {
            $metadata = $this->marginService->buildProductStrategy(
                realCost: $realCost,
                estimatedBits: $estimatedBits,
                retailValue: $retailValue,
            );
        }

        $product->fill([
            'name' => $data['name'] ?? $product->name,
            'description' => $data['description'] ?? $product->description,
            'real_cost' => $realCost,
            'retail_value' => $retailValue,
            'sku' => $data['sku'] ?? $product->sku,
            'status' => $data['status'] ?? $product->status,
            'metadata' => $metadata,
        ]);

        $product->save();

        if ($images !== []) {
            $this->storeImages($product, $images, append: true);
        }

        return $product->fresh(['images']);
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    private function storeImages(Product $product, array $images, bool $append = false): void
    {
        if ($images === []) {
            return;
        }

        if (! $append) {
            $product->images()->delete();
        }

        $startOrder = $append ? (int) $product->images()->max('sort_order') + 1 : 0;
        $primaryPath = null;

        foreach ($images as $index => $image) {
            $path = $image->store('products', 'public');

            $product->images()->create([
                'path' => $path,
                'sort_order' => $startOrder + $index,
                'is_primary' => ! $append && $index === 0,
            ]);

            if (! $append && $index === 0) {
                $primaryPath = $path;
            }
        }

        if ($append && $product->image_path === null && $images !== []) {
            $primaryPath = $product->images()->orderBy('sort_order')->value('path');
        }

        if ($primaryPath !== null) {
            $product->update(['image_path' => $primaryPath]);
        }
    }
}

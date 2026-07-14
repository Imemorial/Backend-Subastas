<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAuctionRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\AuctionResource;
use App\Http\Resources\ProductResource;
use App\Models\Auction;
use App\Models\Product;
use App\Services\Auction\AuctionManagementService;
use App\Services\Auction\WeeklyMarginBalancerService;
use App\Services\Product\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $products = Product::query()->with('images')->latest()->paginate(20);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(
            $request->validated(),
            $this->collectImages($request),
        );

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'product' => new ProductResource($product),
        ], 201);
    }

    public function show(Product $product): ProductResource
    {
        $product->load('images');

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update(
            $product,
            $request->validated(),
            $this->collectImages($request),
        );

        return response()->json([
            'message' => 'Producto actualizado.',
            'product' => new ProductResource($product),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json(['message' => 'Producto eliminado.']);
    }

    /**
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    private function collectImages(StoreProductRequest|UpdateProductRequest $request): array
    {
        $images = $request->file('images', []);

        if ($request->file('image') !== null) {
            array_unshift($images, $request->file('image'));
        }

        return array_values(array_filter($images));
    }
}

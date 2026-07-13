<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWinnerShowcaseRequest;
use App\Http\Requests\Admin\UpdateWinnerShowcaseRequest;
use App\Http\Resources\WinnerShowcaseResource;
use App\Models\WinnerShowcase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;

final class WinnerShowcaseAdminController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $showcases = WinnerShowcase::query()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(20);

        return WinnerShowcaseResource::collection($showcases);
    }

    public function store(StoreWinnerShowcaseRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request->file('image'));
        }

        $showcase = WinnerShowcase::query()->create($data);

        return response()->json([
            'message' => 'Ganador publicado correctamente.',
            'showcase' => new WinnerShowcaseResource($showcase),
        ], 201);
    }

    public function update(UpdateWinnerShowcaseRequest $request, WinnerShowcase $winnerShowcase): JsonResponse
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request->file('image'));
        }

        $winnerShowcase->update($data);

        return response()->json([
            'message' => 'Ganador actualizado.',
            'showcase' => new WinnerShowcaseResource($winnerShowcase->fresh()),
        ]);
    }

    public function destroy(WinnerShowcase $winnerShowcase): JsonResponse
    {
        $winnerShowcase->delete();

        return response()->json(['message' => 'Ganador eliminado.']);
    }

    private function storeImage(UploadedFile $image): string
    {
        return $image->store('winner-showcases', 'public');
    }
}

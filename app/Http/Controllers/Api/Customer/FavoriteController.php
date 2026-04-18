<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ToggleFavoriteRequest;
use App\Models\Tenant;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(
        private readonly FavoriteService $favoriteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ids = $this->favoriteService->getFavoriteTenantIds($request->user());

        return response()->json(['data' => $ids]);
    }

    // お気に入りをトグルする（追加/解除）
    public function toggle(ToggleFavoriteRequest $request, Tenant $tenant): JsonResponse
    {
        $isFavorited = $this->favoriteService->toggleFavorite($request->user(), $tenant);

        return response()->json(['is_favorited' => $isFavorited]);
    }
}

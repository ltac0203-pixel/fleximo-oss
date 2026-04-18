<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ReorderMenuCategoriesRequest;
use App\Http\Requests\Tenant\StoreMenuCategoryRequest;
use App\Http\Requests\Tenant\UpdateMenuCategoryRequest;
use App\Http\Resources\MenuCategoryResource;
use App\Models\MenuCategory;
use App\Services\Menu\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuCategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MenuCategory::class);

        $tenantId = $request->user()->getTenantId();
        $categories = $this->categoryService->getList($tenantId);

        return response()->json([
            'data' => MenuCategoryResource::collection($categories),
        ]);
    }

    // カテゴリを作成する
    public function store(StoreMenuCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', MenuCategory::class);

        $tenantId = $request->user()->getTenantId();
        $category = $this->categoryService->create($tenantId, $request->toDto());

        return response()->json([
            'data' => new MenuCategoryResource($category),
        ], 201);
    }

    public function show(MenuCategory $category): JsonResponse
    {
        $this->authorize('view', $category);

        return response()->json([
            'data' => new MenuCategoryResource($category),
        ]);
    }

    // カテゴリを更新する
    public function update(UpdateMenuCategoryRequest $request, MenuCategory $category): JsonResponse
    {
        $this->authorize('update', $category);

        $category = $this->categoryService->update($category, $request->toDto());

        return response()->json([
            'data' => new MenuCategoryResource($category),
        ]);
    }

    // カテゴリを削除する
    public function destroy(MenuCategory $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->categoryService->delete($category);

        return response()->json(null, 204);
    }

    // カテゴリの並び順を更新する
    public function reorder(ReorderMenuCategoriesRequest $request): JsonResponse
    {
        $this->authorize('reorder', MenuCategory::class);

        $tenantId = $request->user()->getTenantId();
        $this->categoryService->reorder($tenantId, $request->validated()['ordered_ids']);

        return response()->json([
            'message' => '並び順を更新しました。',
        ]);
    }
}

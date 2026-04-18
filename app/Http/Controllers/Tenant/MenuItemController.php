<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AttachOptionGroupRequest;
use App\Http\Requests\Tenant\StoreMenuItemRequest;
use App\Http\Requests\Tenant\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Models\OptionGroup;
use App\Services\Menu\MenuItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function __construct(
        private MenuItemService $menuItemService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MenuItem::class);

        $tenantId = $request->user()->getTenantId();
        $items = $this->menuItemService->getList($tenantId);

        return response()->json([
            'data' => MenuItemResource::collection($items),
        ]);
    }

    // 商品を作成する
    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        $this->authorize('create', MenuItem::class);

        $tenantId = $request->user()->getTenantId();
        $item = $this->menuItemService->create($tenantId, $request->toDto());

        return response()->json([
            'data' => new MenuItemResource($item),
        ], 201);
    }

    public function show(MenuItem $item): JsonResponse
    {
        $this->authorize('view', $item);

        $item->load(['categories', 'optionGroups.options']);

        return response()->json([
            'data' => new MenuItemResource($item),
        ]);
    }

    // 商品を更新する
    public function update(UpdateMenuItemRequest $request, MenuItem $item): JsonResponse
    {
        $this->authorize('update', $item);

        $item = $this->menuItemService->update($item, $request->toDto());

        return response()->json([
            'data' => new MenuItemResource($item),
        ]);
    }

    // 商品を削除する
    public function destroy(MenuItem $item): JsonResponse
    {
        $this->authorize('delete', $item);

        $this->menuItemService->delete($item);

        return response()->json(null, 204);
    }

    // 売り切れ状態を切り替える
    public function toggleSoldOut(MenuItem $item): JsonResponse
    {
        $this->authorize('toggleSoldOut', $item);

        $item = $this->menuItemService->toggleSoldOut($item);

        return response()->json([
            'data' => new MenuItemResource($item),
        ]);
    }

    // オプショングループを紐付ける
    public function attachOptionGroup(AttachOptionGroupRequest $request, MenuItem $item): JsonResponse
    {
        $this->authorize('manageOptionGroups', $item);

        $this->menuItemService->attachOptionGroup($item, $request->validated()['option_group_id']);

        $item->load(['categories', 'optionGroups.options']);

        return response()->json([
            'data' => new MenuItemResource($item),
        ]);
    }

    // オプショングループを解除する
    public function detachOptionGroup(MenuItem $item, OptionGroup $optionGroup): JsonResponse
    {
        $this->authorize('manageOptionGroups', $item);

        $this->menuItemService->detachOptionGroup($item, $optionGroup->id);

        $item->load(['categories', 'optionGroups.options']);

        return response()->json([
            'data' => new MenuItemResource($item),
        ]);
    }
}

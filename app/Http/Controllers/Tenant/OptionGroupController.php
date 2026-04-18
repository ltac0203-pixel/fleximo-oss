<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreOptionGroupRequest;
use App\Http\Requests\Tenant\UpdateOptionGroupRequest;
use App\Http\Resources\OptionGroupResource;
use App\Models\OptionGroup;
use App\Services\Menu\OptionGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OptionGroupController extends Controller
{
    public function __construct(
        private OptionGroupService $optionGroupService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OptionGroup::class);

        $tenantId = $request->user()->getTenantId();
        $optionGroups = $this->optionGroupService->getList($tenantId);

        return response()->json([
            'data' => OptionGroupResource::collection($optionGroups),
        ]);
    }

    // オプショングループを作成する
    public function store(StoreOptionGroupRequest $request): JsonResponse
    {
        $this->authorize('create', OptionGroup::class);

        $tenantId = $request->user()->getTenantId();
        $optionGroup = $this->optionGroupService->create($tenantId, $request->toDto());

        return response()->json([
            'data' => new OptionGroupResource($optionGroup),
        ], 201);
    }

    public function show(OptionGroup $optionGroup): JsonResponse
    {
        $this->authorize('view', $optionGroup);

        $optionGroup->load('options');

        return response()->json([
            'data' => new OptionGroupResource($optionGroup),
        ]);
    }

    // オプショングループを更新する
    public function update(UpdateOptionGroupRequest $request, OptionGroup $optionGroup): JsonResponse
    {
        $this->authorize('update', $optionGroup);

        $optionGroup = $this->optionGroupService->update($optionGroup, $request->toDto());

        return response()->json([
            'data' => new OptionGroupResource($optionGroup),
        ]);
    }

    // オプショングループを削除する
    public function destroy(OptionGroup $optionGroup): JsonResponse
    {
        $this->authorize('delete', $optionGroup);

        $this->optionGroupService->delete($optionGroup);

        return response()->json(null, 204);
    }
}

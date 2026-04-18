<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreOptionRequest;
use App\Http\Requests\Tenant\UpdateOptionRequest;
use App\Http\Resources\OptionResource;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Services\Menu\OptionService;
use Illuminate\Http\JsonResponse;

class OptionController extends Controller
{
    public function __construct(
        private OptionService $optionService
    ) {}

    public function index(OptionGroup $optionGroup): JsonResponse
    {
        $this->authorize('viewAny', [Option::class, $optionGroup]);

        $options = $this->optionService->getList($optionGroup);

        return response()->json([
            'data' => OptionResource::collection($options),
        ]);
    }

    // オプションを作成する
    public function store(StoreOptionRequest $request, OptionGroup $optionGroup): JsonResponse
    {
        $this->authorize('create', [Option::class, $optionGroup]);

        $option = $this->optionService->create($optionGroup, $request->toDto());

        return response()->json([
            'data' => new OptionResource($option),
        ], 201);
    }

    // オプションを更新する
    public function update(UpdateOptionRequest $request, OptionGroup $optionGroup, Option $option): JsonResponse
    {
        $this->authorize('update', $option);

        $option = $this->optionService->update($option, $request->toDto());

        return response()->json([
            'data' => new OptionResource($option),
        ]);
    }

    // オプションを削除する
    public function destroy(OptionGroup $optionGroup, Option $option): JsonResponse
    {
        $this->authorize('delete', $option);

        $this->optionService->delete($option);

        return response()->json(null, 204);
    }
}

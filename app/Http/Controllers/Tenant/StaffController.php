<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreStaffRequest;
use App\Http\Requests\Tenant\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// スタッフ管理コントローラー
class StaffController extends Controller
{
    public function __construct(
        private StaffService $staffService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $tenant = $request->user()->getTenant();
        $staff = $this->staffService->getStaffList($tenant);

        return response()->json([
            'data' => StaffResource::collection($staff),
        ]);
    }

    // スタッフを作成する
    public function store(StoreStaffRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $tenant = $request->user()->getTenant();
        $staff = $this->staffService->createStaff($tenant, $request->toDto());

        return response()->json([
            'data' => new StaffResource($staff),
        ], 201);
    }

    public function show(Request $request, User $staff): JsonResponse
    {
        $this->ensureStaffBelongsToTenant($request, $staff);
        $this->authorize('view', $staff);

        return response()->json([
            'data' => new StaffResource($staff),
        ]);
    }

    // スタッフ情報を更新する
    public function update(UpdateStaffRequest $request, User $staff): JsonResponse
    {
        $this->ensureStaffBelongsToTenant($request, $staff);
        $this->authorize('update', $staff);

        $staff = $this->staffService->updateStaff($staff, $request->toDto());

        return response()->json([
            'data' => new StaffResource($staff),
        ]);
    }

    // スタッフを削除する
    public function destroy(Request $request, User $staff): JsonResponse
    {
        $this->ensureStaffBelongsToTenant($request, $staff);
        $this->authorize('delete', $staff);

        $this->staffService->deleteStaff($staff);

        return response()->json(null, 204);
    }

    // Route Model BindingされたUserが操作元テナントに所属していることを保証する
    private function ensureStaffBelongsToTenant(Request $request, User $staff): void
    {
        $tenantId = $request->user()->getTenantId();

        if ($staff->getTenantId() !== $tenantId) {
            abort(404);
        }
    }
}

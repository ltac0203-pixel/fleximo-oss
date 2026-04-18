<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantProfileRequest;
use App\Http\Resources\TenantDetailResource;
use App\Services\TenantService;
use Illuminate\Http\Request;

// テナントプロフィール管理コントローラー
class ProfileController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {}

    public function show(Request $request): TenantDetailResource
    {
        $tenant = $request->user()->getTenant();

        return new TenantDetailResource($tenant);
    }

    // 自テナントのプロフィールを更新する
    // 自テナントのプロフィールを更新する
    public function update(UpdateTenantProfileRequest $request): TenantDetailResource
    {
        $tenant = $request->user()->getTenant();

        $data = $request->toDto();

        if (empty($data->presentFields)) {
            return new TenantDetailResource($tenant);
        }

        $updatedTenant = $this->tenantService->updateProfile($tenant, $data);

        return new TenantDetailResource($updatedTenant);
    }
}

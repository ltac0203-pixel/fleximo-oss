<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTenantsRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// 顧客向けテナントAPIコントローラー
class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {}

    public function index(SearchTenantsRequest $request): AnonymousResourceCollection
    {
        $tenants = $this->tenantService->search($request);

        return TenantResource::collection($tenants);
    }

    public function show(Tenant $tenant): TenantResource
    {
        $tenant->loadMissing('businessHours');

        return new TenantResource($tenant);
    }
}

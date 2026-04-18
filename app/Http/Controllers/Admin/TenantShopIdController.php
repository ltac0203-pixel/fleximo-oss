<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTenantShopIdRequest;
use App\Http\Resources\TenantShopIdResource;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TenantShopIdController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {}

    // テナント一覧（Shop ID管理）を表示する
    public function index(Request $request): Response
    {
        Gate::authorize('admin.access');
        $search = $request->query('search');
        $tenants = $this->tenantService->searchForShopIdManagement($search);

        return Inertia::render('Admin/TenantShopIds/Index', [
            'tenants' => $tenants->through(
                fn ($tenant) => (new TenantShopIdResource($tenant))->resolve()
            ),
            'searchQuery' => $search,
        ]);
    }

    // テナントのShop IDを更新する
    public function update(UpdateTenantShopIdRequest $request, Tenant $tenant): RedirectResponse
    {
        Gate::authorize('admin.access');
        $tenant->forceFill([
            'fincode_shop_id' => $request->validated('fincode_shop_id'),
        ])->save();

        Cache::forget("tenant:{$tenant->id}:profile");
        TenantService::invalidateActiveTenantListCache();

        return back()->with('success', 'Shop IDを更新しました');
    }
}

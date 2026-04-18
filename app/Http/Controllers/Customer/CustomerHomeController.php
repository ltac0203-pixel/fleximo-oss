<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Services\TenantService;
use Inertia\Inertia;
use Inertia\Response;

class CustomerHomeController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    // 顧客ホームページを表示する
    public function index(): Response
    {
        $tenants = $this->tenantService->getActiveTenants();
        $favoriteTenantIds = auth()->user()->favoriteTenants()->pluck('tenants.id');

        return Inertia::render('Customer/Home/Index', [
            'tenants' => TenantResource::collection($tenants),
            'favoriteTenantIds' => $favoriteTenantIds,
        ]);
    }
}

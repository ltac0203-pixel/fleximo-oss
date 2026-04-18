<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantProfileRequest;
use App\Http\Resources\TenantDetailResource;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TenantProfileController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('tenant.manage');
        $tenant = $request->user()->getTenant();

        return Inertia::render('Tenant/Profile/Index', [
            'tenant' => new TenantDetailResource($tenant),
        ]);
    }

    public function edit(Request $request): Response
    {
        Gate::authorize('tenant.manage');
        $tenant = $request->user()->getTenant();

        return Inertia::render('Tenant/Profile/Edit', [
            'tenant' => new TenantDetailResource($tenant),
        ]);
    }

    public function update(UpdateTenantProfileRequest $request): RedirectResponse
    {
        Gate::authorize('tenant.manage');
        $tenant = $request->user()->getTenant();

        $dto = $request->toDto();

        if (! empty($dto->presentFields)) {
            $this->tenantService->updateProfile($tenant, $dto);
        }

        return redirect()->route('tenant.profile.edit')
            ->with('success', 'プロフィールを更新しました');
    }
}

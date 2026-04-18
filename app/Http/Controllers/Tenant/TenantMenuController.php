<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\OptionGroup;
use App\Services\Menu\TenantMenuPageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantMenuController extends Controller
{
    public function __construct(
        private readonly TenantMenuPageService $tenantMenuPageService
    ) {}

    public function categoriesIndex(Request $request): Response
    {
        return Inertia::render('Tenant/Menu/Categories/Index', $this->tenantMenuPageService->getCategoriesIndexProps());
    }

    public function itemsIndex(Request $request): Response
    {
        return Inertia::render('Tenant/Menu/Items/Index', $this->tenantMenuPageService->getItemsIndexProps());
    }

    public function itemsCreate(Request $request): Response
    {
        return Inertia::render('Tenant/Menu/Items/Create', $this->tenantMenuPageService->getItemsCreateProps());
    }

    public function itemsEdit(Request $request, MenuItem $item): Response
    {
        return Inertia::render('Tenant/Menu/Items/Edit', $this->tenantMenuPageService->getItemsEditProps($item));
    }

    public function optionGroupsIndex(Request $request): Response
    {
        return Inertia::render('Tenant/Menu/OptionGroups/Index', $this->tenantMenuPageService->getOptionGroupsIndexProps());
    }

    public function optionGroupsCreate(Request $request): Response
    {
        return Inertia::render('Tenant/Menu/OptionGroups/Create');
    }

    public function optionGroupsEdit(Request $request, OptionGroup $optionGroup): Response
    {
        return Inertia::render('Tenant/Menu/OptionGroups/Edit', $this->tenantMenuPageService->getOptionGroupsEditProps($optionGroup));
    }
}

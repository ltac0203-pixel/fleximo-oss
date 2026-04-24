<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Domain\Tenant\BusinessHours\BusinessHoursSchedule;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\PublicMenuService;
use App\Support\Seo\PublicPageSeoFactory;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MenuPageController extends Controller
{
    public function __construct(
        private readonly PublicMenuService $menuService,
        private readonly PublicPageSeoFactory $publicPageSeoFactory
    ) {}

    // 顧客向けメニュー表示ページ
    public function index(Tenant $tenant): Response
    {
        // テナントが非アクティブの場合は404
        if (! $tenant->is_active) {
            throw new NotFoundHttpException('このテナントは現在ご利用いただけません。');
        }

        $menu = $this->menuService->getMenu($tenant);

        $status = (new BusinessHoursSchedule($tenant->businessHours))->statusAt();

        $isFavorited = auth()->check() && auth()->user()->isCustomer()
            ? auth()->user()->hasFavoriteTenant($tenant->id)
            : false;

        return Inertia::render('Customer/Tenant/Menu', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'address' => $tenant->address,
                'is_active' => $tenant->is_active,
                'is_open' => $status->isOpen,
                'is_order_paused' => $tenant->is_order_paused,
                'today_business_hours' => $status->todayBusinessHours,
                'is_favorited' => $isFavorited,
            ],
            'menu' => $menu,
            ...$this->publicPageSeoFactory->tenantMenu($tenant, $menu),
        ]);
    }
}

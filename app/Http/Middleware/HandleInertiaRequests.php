<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Resources\TenantDetailResource;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    private const TENANT_CACHE_TTL_SECONDS = 300;

    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        $appUrl = rtrim((string) config('seo.site.base_url', config('app.url', 'https://example.com')), '/');

        $shared = [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    ...$user->only(['id', 'name', 'email', 'email_verified_at']),
                    'role' => $user->role?->value,
                    'is_admin' => $user->isAdmin(),
                    'is_customer' => $user->isCustomer(),
                    'is_tenant_admin' => $user->isTenantAdmin(),
                    'is_tenant_staff' => $user->isTenantStaff(),
                    'should_show_onboarding' => $user->shouldShowOnboarding(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'siteConfig' => [
                'name' => (string) config('app.name', 'Fleximo'),
                'baseUrl' => $appUrl,
                'contactEmail' => (string) config('seo.site.contact_email', 'contact@example.com'),
                'supportEmail' => (string) config('seo.site.support_email', 'support@example.com'),
                'logoUrl' => $appUrl.(string) config('seo.site.logo_path', '/logo.png'),
                'defaultImageUrl' => $appUrl.(string) config('seo.site.default_image_path', '/og-image.svg'),
            ],
            'legal' => [
                'companyName' => (string) config('legal.company_name'),
                'representative' => (string) config('legal.representative'),
                'postalCode' => (string) config('legal.postal_code'),
                'address' => (string) config('legal.address'),
                'addressExtra' => (string) config('legal.address_extra'),
                'contactEmail' => (string) config('legal.contact_email'),
                'businessHours' => (string) config('legal.business_hours'),
                'websiteUrl' => (string) config('legal.website_url'),
            ],
        ];

        // テナント画面のナビや営業時間表示が tenant を前提にするため、毎リクエスト共有する。
        // businessHours はほぼ不変のため Cache::remember で DB クエリを削減。
        if ($user && ($user->isTenantAdmin() || $user->isTenantStaff())) {
            $tenantId = $user->getTenantId();
            if ($tenantId !== null) {
                $tenant = Cache::remember(
                    "tenant:{$tenantId}:profile",
                    self::TENANT_CACHE_TTL_SECONDS,
                    fn () => Tenant::with('businessHours')->find($tenantId)
                );
                if ($tenant) {
                    $shared['tenant'] = new TenantDetailResource($tenant);
                }
            }
        }

        return $shared;
    }
}

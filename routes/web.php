<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TenantShopIdController;
use App\Http\Controllers\Auth\BusinessLoginController;
use App\Http\Controllers\Customer\CardPageController;
use App\Http\Controllers\Customer\CheckoutPageController;
use App\Http\Controllers\Customer\CustomerHomeController;
use App\Http\Controllers\Customer\MenuPageController;
use App\Http\Controllers\Customer\OrderPageController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tenant\KdsPageController;
use App\Http\Controllers\Tenant\TenantDashboardController;
use App\Http\Controllers\Tenant\TenantMenuController;
use App\Http\Controllers\Tenant\TenantProfileController;
use App\Http\Controllers\Tenant\TenantStaffController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

// 事業者向けログイン（ゲストのみアクセス可能）
Route::middleware('guest')->group(function () {
    Route::get('/for-business/login', [BusinessLoginController::class, 'create'])
        ->name('for-business.login');
    Route::post('/for-business/login', [BusinessLoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('for-business.login.store');
});

// ロール別リダイレクト（後方互換性のため /dashboard を維持）
Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'auth.session', 'verified', 'active'])
    ->name('dashboard');

// 顧客向けホームルート
Route::middleware(['auth', 'auth.session', 'verified', 'active', 'role:customer'])
    ->prefix('customer')
    ->name('customer.')
    ->group(function () {
        Route::get('/', [CustomerHomeController::class, 'index'])->name('home');
    });

Route::middleware(['auth', 'auth.session', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // オンボーディングツアーの完了状態管理
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/reset', [OnboardingController::class, 'reset'])->name('onboarding.reset');
});

// テナント管理者・スタッフ共通ルート
Route::middleware(['auth', 'auth.session', 'verified', 'active', 'role:tenant_admin,tenant_staff', 'tenant.context', 'tenant.user-assigned', 'tenant.user-approved'])
    ->prefix('tenant')
    ->name('tenant.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('tenant.dashboard'));
        Route::get('/dashboard', [TenantDashboardController::class, 'index'])->name('dashboard');

        // 店舗設定（閲覧・編集）
        Route::get('/profile', [TenantProfileController::class, 'index'])->name('profile.index');
        Route::patch('/profile', [TenantProfileController::class, 'update'])
            ->middleware('role:tenant_admin')
            ->name('profile.update');

        // スタッフ一覧（閲覧）
        Route::get('/staff', [TenantStaffController::class, 'index'])->name('staff.page');

        // KDS（キッチンディスプレイシステム）
        Route::get('/kds', [KdsPageController::class, 'index'])->name('kds');

        // メニュー管理（閲覧 + 売り切れ切替）
        Route::prefix('menu')->name('menu.')->group(function () {
            Route::get('/categories', [TenantMenuController::class, 'categoriesIndex'])->name('categories.page');
            Route::get('/items', [TenantMenuController::class, 'itemsIndex'])->name('items.page');
            Route::get('/option-groups', [TenantMenuController::class, 'optionGroupsIndex'])->name('option-groups.page');
        });

        // テナント管理者専用（操作）
        Route::middleware('role:tenant_admin')->group(function () {
            // 店舗設定（編集）
            Route::get('/profile/edit', [TenantProfileController::class, 'edit'])->name('profile.edit');

            // メニュー管理（作成・編集）
            Route::prefix('menu')->name('menu.')->group(function () {
                Route::get('/items/create', [TenantMenuController::class, 'itemsCreate'])->name('items.create');
                Route::get('/items/{item}/edit', [TenantMenuController::class, 'itemsEdit'])->name('items.edit');
                Route::get('/option-groups/create', [TenantMenuController::class, 'optionGroupsCreate'])->name('option-groups.create');
                Route::get('/option-groups/{optionGroup}/edit', [TenantMenuController::class, 'optionGroupsEdit'])->name('option-groups.edit');
            });
        });
    });

// 顧客向けWebルート（メニュー閲覧は認証不要）
Route::prefix('order')
    ->name('order.')
    ->group(function () {
        Route::get('/tenant/{tenant:slug}/menu', [MenuPageController::class, 'index'])
            ->name('menu');
    });

// 顧客向けWebルート（認証必須）
Route::middleware(['auth', 'auth.session', 'verified', 'active', 'role:customer'])
    ->prefix('order')
    ->name('order.')
    ->group(function () {
        // カート画面
        Route::inertia('/cart', 'Customer/Cart/Index')->name('cart.show');

        // チェックアウト
        Route::prefix('checkout')->name('checkout.')->group(function () {
            Route::get('/', [CheckoutPageController::class, 'index'])
                ->name('index');
            Route::get('/complete/{order}', [CheckoutPageController::class, 'complete'])
                ->name('complete');
            Route::get('/failed/{order?}', [CheckoutPageController::class, 'failed'])
                ->name('failed');
            Route::get('/callback/paypay/{payment}', [CheckoutPageController::class, 'payPayCallback'])
                ->middleware('signed')
                ->name('callback.paypay');
        });

        // 注文履歴
        Route::get('/orders', [OrderPageController::class, 'index'])
            ->name('orders.index');
        Route::get('/orders/{order}', [OrderPageController::class, 'show'])
            ->name('orders.show');

        // カード管理
        Route::get('/tenant/{tenant:slug}/cards', [CardPageController::class, 'index'])
            ->name('cards.index');
    });

// 3DSコールバック（外部POST受信のためCSRF除外 + 署名URL必須）
Route::middleware(['signed.3ds'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function () {
        Route::match(['get', 'post'], '/order/checkout/callback/3ds/{payment}', [CheckoutPageController::class, 'threeDsCallback'])
            ->whereNumber('payment')
            ->name('order.checkout.callback.3ds');
    });

// 法的ページ（認証不要、公開）
Route::prefix('legal')->name('legal.')->group(function () {
    Route::inertia('/terms', 'Legal/Terms')->name('terms');
    Route::inertia('/privacy-policy', 'Legal/PrivacyPolicy')->name('privacy-policy');
    Route::inertia('/transactions', 'Legal/Transactions')->name('transactions');
    Route::inertia('/tenant-terms', 'Legal/TenantTerms')->name('tenant-terms');
});

// 管理者向けルート
Route::middleware(['auth', 'auth.session', 'verified', 'active', 'role:admin', 'throttle:60,1'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Shop ID管理
        Route::get('/tenant-shop-ids', [TenantShopIdController::class, 'index'])->name('tenant-shop-ids.index');
        Route::patch('/tenant-shop-ids/{tenant}', [TenantShopIdController::class, 'update'])->name('tenant-shop-ids.update');

        // 顧客管理
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
            Route::get('/{customer}/orders', [CustomerController::class, 'orders'])->name('orders');
            Route::post('/{customer}/suspend', [CustomerController::class, 'suspend'])->name('suspend');
            Route::post('/{customer}/ban', [CustomerController::class, 'ban'])->name('ban');
            Route::post('/{customer}/reactivate', [CustomerController::class, 'reactivate'])->name('reactivate');
            Route::get('/{customer}/export', [CustomerController::class, 'export'])
                ->middleware('throttle:5,1')
                ->name('export');
        });
    });

require __DIR__.'/auth.php';

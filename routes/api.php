<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Customer\CardController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\CheckoutController;
use App\Http\Controllers\Api\Customer\FavoriteController;
use App\Http\Controllers\Api\Customer\OrderController;
use App\Http\Controllers\Api\Customer\ReorderController;
use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\Tenant\DashboardController;
use App\Http\Controllers\Api\Tenant\KdsController;
use App\Http\Controllers\Api\Tenant\OrderPauseController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Tenant\MenuCategoryController;
use App\Http\Controllers\Tenant\MenuItemController;
use App\Http\Controllers\Tenant\OptionController;
use App\Http\Controllers\Tenant\OptionGroupController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\StaffController;
use App\Http\Controllers\Webhook\FincodeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ヘルスチェック（認証不要、ロードバランサー・監視ツール向け）
Route::middleware('ip.whitelist')->get('/health', HealthCheckController::class)->name('health');

// 全認証ユーザー向けAPI（テナント検索）
Route::middleware(['auth:sanctum', 'verified', 'active', 'throttle:60,1'])
    ->group(function () {
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::middleware('tenant.route-active')->group(function () {
            Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
            Route::get('/tenants/{tenant}/menu', [MenuController::class, 'index'])->name('tenants.menu');
        });
    });

// テナント管理者・スタッフ向けAPI
Route::middleware(['auth:sanctum', 'verified', 'active', 'role:tenant_admin,tenant_staff', 'tenant.user-assigned', 'tenant.user-approved', 'throttle:60,1'])
    ->prefix('tenant')
    ->name('tenant.')
    ->group(function () {
        // プロフィール（API）
        Route::prefix('profile')->name('profile.api.')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('show');
            Route::middleware('role:tenant_admin')
                ->patch('/', [ProfileController::class, 'update'])
                ->name('update');
        });

        // スタッフ管理（テナント管理者のみ、Policyで制御）
        Route::apiResource('staff', StaffController::class);

        // カテゴリ管理
        Route::post('menu/categories/reorder', [MenuCategoryController::class, 'reorder'])
            ->name('menu.categories.reorder');
        Route::apiResource('menu/categories', MenuCategoryController::class)
            ->parameters(['categories' => 'category'])
            ->names('menu.categories');

        // 商品管理
        Route::patch('menu/items/{item}/sold-out', [MenuItemController::class, 'toggleSoldOut'])
            ->name('menu.items.sold-out');
        Route::post('menu/items/{item}/option-groups', [MenuItemController::class, 'attachOptionGroup'])
            ->name('menu.items.option-groups.attach');
        Route::delete('menu/items/{item}/option-groups/{optionGroup}', [MenuItemController::class, 'detachOptionGroup'])
            ->name('menu.items.option-groups.detach');
        Route::apiResource('menu/items', MenuItemController::class)
            ->parameters(['items' => 'item'])
            ->names('menu.items');

        // オプショングループ管理
        Route::apiResource('option-groups', OptionGroupController::class)
            ->parameters(['option-groups' => 'optionGroup']);
        Route::apiResource('option-groups.options', OptionController::class)
            ->except(['show'])
            ->parameters(['option-groups' => 'optionGroup'])
            ->scoped();

        // KDS（キッチンディスプレイシステム）（テナント管理者・スタッフ共通）
        Route::prefix('kds')->name('kds.')->middleware('throttle:60,1')->group(function () {
            Route::get('/orders', [KdsController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [KdsController::class, 'show'])->name('orders.show');
            Route::patch('/orders/{order}/status', [KdsController::class, 'updateStatus'])->name('orders.status');
        });

        // 注文受付一時停止（テナント管理者・スタッフ共通）
        Route::prefix('order-pause')->name('order-pause.')->group(function () {
            Route::get('/status', [OrderPauseController::class, 'status'])->name('status');
            Route::post('/toggle', [OrderPauseController::class, 'toggle'])->name('toggle');
        });

        // ダッシュボード（テナント管理者・スタッフ共通）
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/summary', [DashboardController::class, 'summary'])->name('summary');
            Route::get('/hourly', [DashboardController::class, 'hourly'])->name('hourly');
            Route::get('/sales', [DashboardController::class, 'sales'])->name('sales');
            Route::get('/top-items', [DashboardController::class, 'topItems'])->name('top-items');
            Route::get('/payment-methods', [DashboardController::class, 'paymentMethods'])->name('payment-methods');
            Route::get('/customer-insights', [DashboardController::class, 'customerInsights'])->name('customer-insights');
            Route::get('/export/csv', [DashboardController::class, 'exportCsv'])->name('export.csv');
        });
    });

// 顧客向けAPI
Route::middleware(['auth:sanctum', 'verified', 'active', 'role:customer', 'throttle:60,1'])
    ->prefix('customer')
    ->name('customer.')
    ->group(function () {
        // カート機能
        Route::prefix('cart')->name('cart.')->group(function () {
            Route::get('/', [CartController::class, 'index'])->name('index');
            Route::post('/items', [CartController::class, 'addItem'])->name('items.store');
            Route::patch('/items/{cartItem}', [CartController::class, 'updateItem'])->name('items.update');
            Route::delete('/items/{cartItem}', [CartController::class, 'removeItem'])->name('items.destroy');
            Route::delete('/{cart}', [CartController::class, 'clearCart'])->name('destroy');
        });

        // 注文履歴
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            Route::get('/{order}/status', [OrderController::class, 'status'])
                ->middleware('throttle:120,1')
                ->name('status');
            Route::post('/{order}/reorder', ReorderController::class)->name('reorder');
        });

        // チェックアウト・決済
        Route::post('/checkout', [CheckoutController::class, 'checkout'])
            ->middleware(['throttle:payment', 'idempotent'])
            ->name('checkout');
        Route::post('/payments/finalize', [CheckoutController::class, 'finalize'])
            ->middleware(['throttle:finalize', 'idempotent'])
            ->name('payments.finalize');
        Route::post('/payments/3ds-callback', [CheckoutController::class, 'process3dsCallback'])
            ->middleware(['throttle:payment', 'idempotent'])
            ->name('payments.3ds-callback');

        // お気に入り店舗
        Route::prefix('favorites')->name('favorites.')->group(function () {
            Route::get('/', [FavoriteController::class, 'index'])->name('index');
            Route::post('/tenants/{tenant}', [FavoriteController::class, 'toggle'])->name('toggle');
        });

        // カード管理
        Route::prefix('tenants/{tenant}/cards')
            ->middleware('tenant.route-active')
            ->name('cards.')
            ->group(function () {
                Route::get('/', [CardController::class, 'index'])->name('index');
                Route::post('/', [CardController::class, 'store'])->name('store');
                Route::delete('/{card}', [CardController::class, 'destroy'])->name('destroy');
            });
    });

// Webhook（認証不要、CSRF除外、署名検証あり、レート制限あり）
Route::prefix('webhooks')
    ->middleware('throttle:webhook')
    ->name('webhooks.')
    ->group(function () {
        // fincode 決済 Webhook
        Route::post('/payments/{tenantId}', [FincodeWebhookController::class, 'handle'])
            ->name('payments');
    });

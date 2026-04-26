<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Policies\CartItemPolicy;
use App\Policies\CartPolicy;
use App\Policies\MenuCategoryPolicy;
use App\Policies\MenuItemPolicy;
use App\Policies\OptionGroupPolicy;
use App\Policies\OptionPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\StaffPolicy;
use App\Services\Webhook\WebhookSignatureVerifier;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    private const RATE_LIMIT_PAYMENT_PER_MINUTE = 10;

    private const RATE_LIMIT_FINALIZE_PER_MINUTE = 30;

    private const RATE_LIMIT_WEBHOOK_PER_MINUTE = 30;

    public function register(): void
    {
        $this->app->bind(WebhookSignatureVerifier::class, function ($app) {
            $secret = config('fincode.webhook_secret');

            return new WebhookSignatureVerifier(is_string($secret) ? $secret : '');
        });
    }

    // アプリケーションサービスの初期化処理を行う。
    public function boot(): void
    {
        // 本番環境では実行コンテキストを問わずセッション設定をフェイルファスト検証する
        $this->validateProductionSessionSecurity();
        $this->validateFincodeConfig();
        // 開発・テスト時にN+1問題を早期発見するため、lazy loadを例外で検知する（本番では性能影響を避け無効）
        Model::preventLazyLoading(app()->environment('local', 'testing'));

        // Carbon の diffForHumans() などをアプリの locale に合わせる。
        // 'ja' 以外は 'en' にフォールバック（OSS 多言語追加時はマップを拡張）。
        Carbon::setLocale(app()->getLocale() === 'ja' ? 'ja' : 'en');

        JsonResource::withoutWrapping();

        Vite::prefetch(concurrency: 3);

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $fromAddress = config('mail.from_addresses.no_reply.address', config('mail.from.address'));
            $fromName = config('mail.from_addresses.no_reply.name', config('mail.from.name'));

            $expireMinutes = (int) config('auth.verification.expire', 60);

            return (new MailMessage)
                ->subject(__('mail.verify_email.subject'))
                ->from($fromAddress, $fromName)
                ->greeting(__('mail.verify_email.greeting'))
                ->line(__('mail.verify_email.line_intro'))
                ->action(__('mail.verify_email.action'), $url)
                ->line(__('mail.verify_email.line_expire', ['minutes' => $expireMinutes]))
                ->line(__('mail.verify_email.line_disclaimer'))
                ->salutation(__('mail.verify_email.salutation'));
        });

        // パスワードポリシー強化: 大文字小文字混在・数字必須・漏洩パスワード拒否（本番のみ）
        Password::defaults(function () {
            $rule = Password::min(8)
                ->mixedCase()
                ->numbers();

            return app()->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });

        RateLimiter::for('payment', function ($request) {
            return Limit::perMinute(self::RATE_LIMIT_PAYMENT_PER_MINUTE)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('finalize', function ($request) {
            return Limit::perMinute(self::RATE_LIMIT_FINALIZE_PER_MINUTE)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('webhook', function ($request) {
            return Limit::perMinute(self::RATE_LIMIT_WEBHOOK_PER_MINUTE)->by($request->route('tenantId') ?? $request->ip());
        });

        // Laravelの自動検出規約に依存せず、Policy対応を確実にするため明示的に登録する
        Gate::policy(User::class, StaffPolicy::class);
        Gate::policy(MenuCategory::class, MenuCategoryPolicy::class);
        Gate::policy(MenuItem::class, MenuItemPolicy::class);
        Gate::policy(OptionGroup::class, OptionGroupPolicy::class);
        Gate::policy(Option::class, OptionPolicy::class);
        Gate::policy(Cart::class, CartPolicy::class);
        Gate::policy(CartItem::class, CartItemPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);

        // モデルに紐付かない認可ゲート
        Gate::define('dashboard.view', function (User $user): bool {
            return ($user->isTenantAdmin() || $user->isTenantStaff())
                && $user->getTenantId() !== null;
        });

        Gate::define('dashboard.exportCsv', function (User $user): bool {
            return ($user->isTenantAdmin() || $user->isTenantStaff())
                && $user->getTenantId() !== null;
        });

        Gate::define('admin.access', function (User $user): bool {
            return $user->isAdmin();
        });

        Gate::define('tenant.manage', function (User $user): bool {
            return ($user->isTenantAdmin() || $user->isTenantStaff())
                && $user->getTenantId() !== null;
        });

        Gate::define('card.viewAny', function (User $user): bool {
            return $user->isCustomer();
        });

        Gate::define('card.create', function (User $user): bool {
            return $user->isCustomer();
        });

        Gate::define('card.delete', function (User $user): bool {
            return $user->isCustomer();
        });

        // 顧客管理
        Gate::define('admin.customer.viewAny', function (User $user): bool {
            return $user->isAdmin();
        });

        Gate::define('admin.customer.view', function (User $user, User $customer): bool {
            return $user->isAdmin() && $customer->isCustomer();
        });

        Gate::define('admin.customer.suspend', function (User $user, User $customer): bool {
            return $user->isAdmin() && $customer->isCustomer();
        });

        Gate::define('admin.customer.ban', function (User $user, User $customer): bool {
            return $user->isAdmin() && $customer->isCustomer();
        });

        Gate::define('admin.customer.reactivate', function (User $user, User $customer): bool {
            return $user->isAdmin() && $customer->isCustomer() && $customer->isAccountRestricted();
        });

        Gate::define('admin.customer.export', function (User $user, User $customer): bool {
            return $user->isAdmin() && $customer->isCustomer();
        });
    }

    private function validateProductionSessionSecurity(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        if (! config('session.secure')) {
            throw new \RuntimeException(
                '本番環境でSESSION_SECURE_COOKIEがfalseです。HTTPS環境ではtrueに設定してください。'
            );
        }

        if (! config('session.encrypt')) {
            throw new \RuntimeException(
                '本番環境でSESSION_ENCRYPTがfalseです。セッションデータ保護のためtrueを推奨します。'
            );
        }
    }

    private function validateFincodeConfig(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        if (empty(config('fincode.webhook_secret'))) {
            throw new \RuntimeException(
                '本番環境でFINCODE_WEBHOOK_SECRETが設定されていません。Webhook署名検証に必要です。'
            );
        }
    }
}

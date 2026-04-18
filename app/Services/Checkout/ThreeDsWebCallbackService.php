<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ThreeDsWebCallbackService
{
    private const AUTO_LOGIN_CACHE_KEY_PREFIX = '3ds_auto_login_consumed:';

    private const AUTO_LOGIN_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly CheckoutOrchestrator $checkoutOrchestrator,
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Payment $payment): ThreeDsWebCallbackOutcome
    {
        $this->establishTenantContext($payment);

        if (! $payment->order || ! $payment->order->user) {
            return ThreeDsWebCallbackOutcome::failure('order_not_found');
        }

        if ($payment->status->isTerminal()) {
            return ThreeDsWebCallbackOutcome::failure('already_processed');
        }

        $ownerContextError = $this->ensurePaymentOwnerContext($request, $payment);
        if ($ownerContextError !== null) {
            return ThreeDsWebCallbackOutcome::failure($ownerContextError);
        }

        $input = $this->extractCallbackInput($request);
        if ($input->param === '') {
            return ThreeDsWebCallbackOutcome::failure('3ds_missing_param');
        }

        try {
            $result = $this->checkoutOrchestrator->process3dsCallback(
                $payment,
                $input->param,
                $input->event
            );

            return ThreeDsWebCallbackOutcome::success($result);
        } catch (\Throwable $e) {
            $this->logProcessingFailure($request, $payment, $input->event, $e);

            return ThreeDsWebCallbackOutcome::failure('3ds_verification_failed');
        }
    }

    private function establishTenantContext(Payment $payment): void
    {
        $this->tenantContext->setTenant($payment->tenant_id);
    }

    private function ensurePaymentOwnerContext(Request $request, Payment $payment): ?string
    {
        if (Auth::check()) {
            if (Auth::id() !== $payment->order->user_id) {
                Log::warning('3DS callback: authenticated user does not own payment', [
                    'auth_user_id' => Auth::id(),
                    'payment_user_id' => $payment->order->user_id,
                    'payment_id' => $payment->id,
                ]);

                return 'unauthorized';
            }

            return null;
        }

        return $this->performAutoLogin($request, $payment);
    }

    private function performAutoLogin(Request $request, Payment $payment): ?string
    {
        $autoLoginCacheKey = $this->autoLoginCacheKey($payment);
        if (Cache::has($autoLoginCacheKey)) {
            Log::warning('3DS callback: auto-login already consumed for this payment', [
                'payment_id' => $payment->id,
                'ip' => $request->ip(),
            ]);

            return 'already_processed';
        }

        Auth::login($payment->order->user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->regenerateToken();
        }

        Cache::put($autoLoginCacheKey, true, self::AUTO_LOGIN_CACHE_TTL_SECONDS);

        Log::info('3DS callback: auto-login performed for payment owner', [
            'user_id' => $payment->order->user_id,
            'payment_id' => $payment->id,
            'ip' => $request->ip(),
        ]);

        return null;
    }

    private function extractCallbackInput(Request $request): ThreeDsWebCallbackInput
    {
        $param = $this->firstNonEmptyString(
            $request->input('param'),
            $request->query('param'),
            $request->input('MD'),
            $request->query('MD'),
            $request->input('PaRes'),
            $request->query('PaRes')
        );

        $event = $this->firstNonEmptyString(
            $request->input('event'),
            $request->query('event')
        );

        if ($event === '' && ($request->has('MD') || $request->has('PaRes'))) {
            $event = 'AuthResultReady';
        }

        return new ThreeDsWebCallbackInput(
            param: $param,
            event: $event !== '' ? $event : null,
        );
    }

    private function firstNonEmptyString(mixed ...$values): string
    {
        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function logProcessingFailure(Request $request, Payment $payment, ?string $event, \Throwable $e): void
    {
        $latestPayment = $this->reloadPaymentSafely($payment);

        Log::error('3DS callback processing failed on web callback route', [
            'payment_id' => $latestPayment->id,
            'order_id' => $latestPayment->order_id,
            'payment_status' => $latestPayment->status->value,
            'order_status' => $latestPayment->order?->status?->value,
            'fincode_id' => $latestPayment->fincode_id,
            'has_fincode_id' => $latestPayment->fincode_id !== null && $latestPayment->fincode_id !== '',
            'has_access_id' => $latestPayment->fincode_access_id !== null && $latestPayment->fincode_access_id !== '',
            'event' => $event,
            'param_source' => $this->resolveParamSource($request),
            'message' => $e->getMessage(),
        ]);
    }

    private function reloadPaymentSafely(Payment $payment): Payment
    {
        try {
            return Payment::withoutGlobalScope(TenantScope::class)
                ->with([
                    'tenant',
                    'order' => fn ($query) => $query
                        ->withoutGlobalScope(TenantScope::class)
                        ->with('user'),
                ])
                ->findOrFail($payment->id);
        } catch (\Throwable $refreshError) {
            Log::warning('Failed to reload payment on 3DS callback error', [
                'payment_id' => $payment->id,
                'message' => $refreshError->getMessage(),
            ]);

            return $payment;
        }
    }

    private function resolveParamSource(Request $request): string
    {
        if ($request->has('param')) {
            return 'param';
        }

        if ($request->has('MD')) {
            return 'MD';
        }

        if ($request->has('PaRes')) {
            return 'PaRes';
        }

        return 'unknown';
    }

    private function autoLoginCacheKey(Payment $payment): string
    {
        return self::AUTO_LOGIN_CACHE_KEY_PREFIX.$payment->id;
    }
}

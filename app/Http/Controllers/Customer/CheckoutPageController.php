<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CardResource;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderDetailResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use App\Services\CartService;
use App\Services\Checkout\ThreeDsWebCallbackService;
use App\Services\FincodeCustomerService;
use App\Services\OrderService;
use App\Services\ThreeDsAuthResult;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutPageController extends Controller
{
    use AuthorizesRequests;

    private const ERROR_MESSAGES = [
        'payment_failed' => '決済処理に失敗しました。',
        'order_not_found' => '注文情報が見つかりません。',
        'already_processed' => 'この決済は既に処理済みです。',
        'unauthorized' => 'この決済にアクセスする権限がありません。',
        '3ds_missing_param' => '3DS認証パラメータがありません。',
        '3ds_verification_failed' => '3DS認証の確認に失敗しました。',
        '3ds_auth_failed' => '3DS認証に失敗しました。別のカードをお試しください。',
    ];

    public function __construct(
        protected OrderService $orderService,
        protected FincodeCustomerService $fincodeCustomerService,
        protected CartService $cartService,
        protected ThreeDsWebCallbackService $threeDsWebCallbackService
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        // Checkout は単一テナント前提なので、複数カート保持中でも注文対象は 1 件に絞る。
        $cart = $this->cartService->getCheckoutCart($request->user());

        if (! $cart) {
            return redirect()->route('order.cart.show');
        }

        if (! $cart->tenant->is_open) {
            return redirect()->route('order.cart.show')
                ->with('error', '店舗が営業時間外のため、注文手続きに進めません。');
        }

        $savedCards = $this->fincodeCustomerService->getCards(
            $request->user(),
            $cart->tenant
        );

        $fincodePublicKey = config('fincode.public_key');
        if (empty($fincodePublicKey)) {
            \Log::error('Fincode public key is not configured. Checkout page will be non-functional.', [
                'config_cached' => app()->configurationIsCached(),
                'user_id' => $request->user()->id,
            ]);
        }

        return Inertia::render('Customer/Checkout/Index', [
            'cart' => (new CartResource($cart))->resolve(),
            'fincodePublicKey' => $fincodePublicKey,
            'isProduction' => (bool) config('fincode.is_production'),
            'savedCards' => CardResource::collection($savedCards)->resolve(),
        ]);
    }

    public function complete(int $order): Response
    {
        $orderModel = $this->resolveOrder($order);
        $this->authorize('view', $orderModel);

        $orderWithDetails = $this->orderService->getOrderWithDetails($orderModel);

        return Inertia::render('Customer/Checkout/Complete', [
            'order' => (new OrderDetailResource($orderWithDetails))->resolve(),
        ]);
    }

    public function failed(Request $request, ?int $order = null): Response
    {
        $orderModel = null;
        if ($order !== null) {
            $orderModel = $this->resolveOrder($order);
            $this->authorize('view', $orderModel);
        }

        $errorKey = $request->query('error', 'payment_failed');
        $errorMessage = self::ERROR_MESSAGES[$errorKey] ?? self::ERROR_MESSAGES['payment_failed'];

        return Inertia::render('Customer/Checkout/Failed', [
            'order' => $orderModel ? (new OrderDetailResource($orderModel->loadCustomerDetail()))->resolve() : null,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function payPayCallback(Request $request, int $payment): Response
    {
        $paymentModel = $this->resolvePayment($payment);
        $this->authorize('view', $paymentModel);

        return Inertia::render('Customer/Checkout/PayPayCallback', [
            'payment' => [
                'id' => $paymentModel->id,
                'status' => $paymentModel->status->value,
                'order_id' => $paymentModel->order_id,
            ],
            // 常にfinalize APIで実際の決済状態を照会するため、Processing状態でもtrueにする
            // confirm()がfincode APIを呼び、CAPTURED/pending/failedを正確に判定する
            'success' => ! $paymentModel->isFailed(),
        ]);
    }

    public function threeDsCallback(Request $request, int $payment): RedirectResponse
    {
        $paymentModel = $this->resolvePayment($payment);
        // 認証・認可はサービス側（ensurePaymentOwnerContext）で処理する。
        // 未認証ユーザーの自動ログインをサポートするため、ここでは authorize を呼ばない。
        $outcome = $this->threeDsWebCallbackService->handle($request, $paymentModel);

        if ($outcome->hasError()) {
            return $this->redirectToCheckoutFailed($paymentModel, $outcome->errorKey() ?? 'payment_failed');
        }

        return $this->redirectByThreeDsResult(
            $outcome->result() ?? ThreeDsAuthResult::failed($paymentModel),
            $paymentModel
        );
    }

    private function redirectByThreeDsResult(ThreeDsAuthResult $result, Payment $payment): RedirectResponse
    {
        if ($result->requiresRedirect() && $result->challengeUrl !== null) {
            return redirect()->away($result->challengeUrl);
        }

        if ($result->isAuthenticated()) {
            return redirect()->route('order.checkout.complete', ['order' => $payment->order_id]);
        }

        return $this->redirectToCheckoutFailed($payment, '3ds_auth_failed');
    }

    private function redirectToCheckoutFailed(Payment $payment, string $errorKey): RedirectResponse
    {
        return redirect()->route('order.checkout.failed', ['order' => $payment->order_id, 'error' => $errorKey]);
    }

    // 顧客画面はテナント URL を持たないため、所有者ポリシーで弾く前に TenantScope を外して読む。
    private function resolveOrder(int $orderId): Order
    {
        return Order::withoutGlobalScope(TenantScope::class)
            ->findOrFail($orderId);
    }

    private function resolvePayment(int $paymentId): Payment
    {
        return Payment::withoutGlobalScope(TenantScope::class)
            ->with([
                'tenant',
                'order' => fn ($query) => $query
                    ->withoutGlobalScope(TenantScope::class)
                    ->with('user'),
            ])
            ->findOrFail($paymentId);
    }
}

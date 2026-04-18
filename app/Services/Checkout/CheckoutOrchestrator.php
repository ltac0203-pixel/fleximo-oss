<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\EmptyCartException;
use App\Exceptions\OrderPausedException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\TenantClosedException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\CartService;
use App\Services\CheckoutResult;
use App\Services\PaymentService;
use App\Services\PayPayPaymentService;
use App\Services\ThreeDsAuthResult;
use App\Services\ThreeDsPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutOrchestrator
{
    public function __construct(
        private readonly CheckoutValidationService $validationService,
        private readonly OrderCreationService $orderCreationService,
        private readonly PaymentService $paymentService,
        private readonly ThreeDsPaymentService $threeDsPaymentService,
        private readonly PayPayPaymentService $payPayPaymentService,
        private readonly CartService $cartService
    ) {}

    // チェックアウトを処理する
    public function processCheckout(Cart $cart, PaymentMethod $paymentMethod, ?int $cardId = null): CheckoutResult
    {
        // トランザクション開始前に不正な状態を検出し、不要なDB操作を防ぐ
        $this->validationService->validateForCheckout($cart, $paymentMethod);

        $options = [];
        if ($cardId !== null) {
            $options['card_id'] = $cardId;
        }

        // 同一カートの同時チェックアウトを防止するため排他ロックを取得し、
        // 注文作成は短いトランザクションで完結させる
        $order = DB::transaction(function () use ($cart) {
            $lockedCart = Cart::lockForUpdate()->find($cart->id);
            if ($lockedCart === null || $lockedCart->isEmpty()) {
                throw new EmptyCartException;
            }

            // トランザクション内でテナント営業状態を再チェックし、
            // バリデーション後～トランザクション間のTOCTOU攻撃を防止する
            $tenant = Tenant::lockForUpdate()->findOrFail($lockedCart->tenant_id);
            if (! $tenant->isActive()) {
                throw new TenantClosedException;
            }
            if ($tenant->is_order_paused) {
                throw new OrderPausedException;
            }

            return $this->orderCreationService->createFromCart($lockedCart);
        });

        // 外部API呼び出しはトランザクション外で実行し、ロック時間を最小化する
        $paymentResult = $this->paymentService->initiate($order, $paymentMethod, $options);

        // 決済開始後のカートクリア失敗は可用性優先で吸収し、決済開始結果を返す
        // 最大2回リトライし、全失敗時はフラグでフロントに通知する
        $cartClearFailed = false;
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $this->cartService->clearItems($cart);
                break;
            } catch (\Throwable $e) {
                Log::error('Failed to clear cart items after payment initiation', [
                    'cart_id' => $cart->id,
                    'cart_user_id' => $cart->user_id,
                    'cart_tenant_id' => $cart->tenant_id,
                    'order_id' => $order->id,
                    'payment_id' => $paymentResult->payment->id,
                    'payment_method' => $paymentMethod->value,
                    'attempt' => $attempt,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
                if ($attempt >= 2) {
                    $cartClearFailed = true;
                }
            }
        }
        $order->refresh();

        return CheckoutResult::fromPaymentInitiation($order, $paymentResult, $cartClearFailed);
    }

    // 決済を確定する
    public function finalizePayment(Payment $payment): ?Order
    {
        // Webhook先行で既に確定済みの場合は成功として扱い、完了画面へ遷移できるようにする
        if ($payment->status === PaymentStatus::Completed) {
            $order = $payment->order;
            $order->refresh();

            return $order;
        }

        // 失敗済みの決済は再確定できないため、即時にエラーを返す
        if ($payment->status === PaymentStatus::Failed) {
            throw new PaymentFailedException(
                $payment,
                null,
                'この決済は失敗しています。'
            );
        }

        // 二重決済を防止するため、未処理の決済のみ確定を許可する
        if ($payment->status !== PaymentStatus::Pending && $payment->status !== PaymentStatus::Processing) {
            throw new PaymentFailedException(
                $payment,
                null,
                'この決済は既に処理済みです。'
            );
        }

        // PayPay決済のコールバック後、決済ゲートウェイに最終確認を行う
        $confirmed = $this->payPayPaymentService->confirm($payment);

        // fincode APIでCAPTURED状態でない場合は未確定として扱う
        if (! $confirmed) {
            return null;
        }

        // 決済確定によりステータスが変わるため、最新の注文状態を返す
        $order = $payment->order;
        $order->refresh();

        return $order;
    }

    // 3DS用に決済を実行する（カード情報送信→acs_url取得）
    public function executePaymentFor3ds(
        Payment $payment,
        ?string $token = null,
        bool $saveCard = false,
        bool $saveAsDefault = false
    ): string {
        // 完了済みや失敗済みの決済に対する決済実行を防ぐ
        if ($payment->status !== PaymentStatus::Pending && $payment->status !== PaymentStatus::Processing) {
            throw new PaymentFailedException(
                $payment,
                null,
                'この決済は既に処理済みです。'
            );
        }

        return $this->threeDsPaymentService->executePayment($payment, $token, $saveCard, $saveAsDefault);
    }

    // 3DSコールバックを処理する（3DS Method完了後 or チャレンジ完了後）
    public function process3dsCallback(Payment $payment, string $param, ?string $event = null): ThreeDsAuthResult
    {
        // DB上の最新ステータスで判定し、ブラウザ復帰時の遅延による状態不一致を吸収する
        $payment->refresh();

        // Webhook先行で既に完了済みの場合は成功として扱う
        if ($payment->status === PaymentStatus::Completed) {
            return ThreeDsAuthResult::authenticated($payment);
        }

        // 3DSコールバック時にPendingが残っていた場合は、認証処理に進むためProcessingへ昇格させる
        if ($payment->status === PaymentStatus::Pending) {
            $payment->markAsProcessing();
            $payment->refresh();
        }

        // 3DSコールバックはProcessing状態でのみ有効。他の状態は不正なリクエストの可能性がある
        if ($payment->status !== PaymentStatus::Processing) {
            throw new PaymentFailedException(
                $payment,
                null,
                'この決済は3DS認証待ちではありません。'
            );
        }

        // チャレンジ完了後: 認証結果をGETで取得し、決済を確定する
        if ($event === 'AuthResultReady') {
            return $this->threeDsPaymentService->confirmAndExecute($payment, $param);
        }

        // 3DS Method完了後/スキップ後: paramを使ってPUTで認証を実行する
        return $this->threeDsPaymentService->executeAuthentication($payment, $param);
    }
}

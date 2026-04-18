<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CheckoutRequest;
use App\Http\Requests\Customer\FinalizePaymentRequest;
use App\Http\Requests\Customer\ThreeDsCallbackRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Checkout\CheckoutOrchestrator;
use App\Services\ThreeDsAuthResult;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutOrchestrator $checkoutOrchestrator
    ) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $cart = $request->getCart();
        $this->authorize('checkout', $cart);

        $result = $this->checkoutOrchestrator->processCheckout(
            $cart,
            $request->getPaymentMethod(),
            $request->getCardId()
        );

        return response()->json([
            'data' => $result->toArray(),
        ], 201);
    }

    // 決済を確定する（3DS決済実行→acs_urlリダイレクト、またはPayPay確認）
    public function finalize(FinalizePaymentRequest $request): JsonResponse
    {
        $payment = $request->getPayment();
        $token = $request->getToken();

        // トークンなし＋非保存カード＝PayPay決済なので、外部決済完了後の確認フローに進む
        if ($token === null && ! $payment->usesSavedCard()) {
            $order = $this->checkoutOrchestrator->finalizePayment($payment);

            if ($order === null) {
                return response()->json([
                    'data' => [
                        'payment_pending' => true,
                        'order_id' => $payment->order_id,
                    ],
                ]);
            }

            return $this->orderSuccessResponse($order);
        }

        // カード決済（新規/保存済み共通）: 決済実行→acs_url取得→フロントにリダイレクト指示
        $acsUrl = $this->checkoutOrchestrator->executePaymentFor3ds(
            $payment,
            $token,
            $request->getSaveCard(),
            $request->getSaveAsDefault()
        );

        return response()->json([
            'data' => [
                'requires_3ds_redirect' => true,
                'redirect_url' => $acsUrl,
                'payment_id' => $payment->id,
            ],
        ]);
    }

    // 3DSコールバックを処理する（3DS Method完了後 or チャレンジ完了後）
    public function process3dsCallback(ThreeDsCallbackRequest $request): JsonResponse
    {
        $payment = $request->getPayment();
        $param = $request->getParam();
        $event = $request->getEvent();

        $result = $this->checkoutOrchestrator->process3dsCallback($payment, $param, $event);

        return $this->handle3dsResult($result, $payment);
    }

    // 3DS認証結果に応じてリダイレクト・成功・失敗のレスポンスを返す
    private function handle3dsResult(ThreeDsAuthResult $result, Payment $payment): JsonResponse
    {
        // カード発行会社が追加認証を要求した場合、ユーザーを発行会社の認証画面へ誘導する
        if ($result->requiresRedirect()) {
            return response()->json([
                'data' => [
                    'requires_3ds_redirect' => true,
                    'redirect_url' => $result->challengeUrl,
                    'payment_id' => $payment->id,
                ],
            ]);
        }

        // フリクションレス認証が成功し決済確定済みのため、注文情報を返却
        if ($result->isAuthenticated()) {
            return $this->orderSuccessResponse($payment->order);
        }

        // 3DS認証失敗時はカード利用を拒否し、別手段への切り替えを促す
        return response()->json([
            'error' => [
                'message' => '3DS認証に失敗しました。別のカードをお試しください。',
            ],
        ], 400);
    }

    // 注文成功レスポンスを生成する
    private function orderSuccessResponse(Order $order): JsonResponse
    {
        return response()->json([
            'data' => [
                'order' => new OrderResource($order->load('items.options')),
            ],
        ]);
    }
}

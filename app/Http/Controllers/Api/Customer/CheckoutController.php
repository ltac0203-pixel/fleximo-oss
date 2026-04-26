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

    public function finalize(FinalizePaymentRequest $request): JsonResponse
    {
        $payment = $request->getPayment();
        $token = $request->getToken();

        // 非カード系フローは即時確定できないため、外部決済結果を再確認する経路に分ける。
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

        // 新規カードと保存済みカードで 3DS の戻り先を揃え、フロント分岐を増やさない。
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

    public function process3dsCallback(ThreeDsCallbackRequest $request): JsonResponse
    {
        $payment = $request->getPayment();
        $param = $request->getParam();
        $event = $request->getEvent();

        $result = $this->checkoutOrchestrator->process3dsCallback($payment, $param, $event);

        return $this->handle3dsResult($result, $payment);
    }

    private function handle3dsResult(ThreeDsAuthResult $result, Payment $payment): JsonResponse
    {
        // 認証を途中で打ち切ると決済状態と画面状態がずれるため、追加認証が必要なら issuer へ戻す。
        if ($result->requiresRedirect()) {
            return response()->json([
                'data' => [
                    'requires_3ds_redirect' => true,
                    'redirect_url' => $result->challengeUrl,
                    'payment_id' => $payment->id,
                ],
            ]);
        }

        // フリクションレス成功時は注文確定まで完了しているので、そのまま成功画面へ進める。
        if ($result->isAuthenticated()) {
            return $this->orderSuccessResponse($payment->order);
        }

        // 認証失敗を曖昧に扱うと再試行条件が見えなくなるため、カード変更を促して明示的に止める。
        return response()->json([
            'error' => [
                'message' => '3DS認証に失敗しました。別のカードをお試しください。',
            ],
        ], 400);
    }

    private function orderSuccessResponse(Order $order): JsonResponse
    {
        return response()->json([
            'data' => [
                'order' => new OrderResource($order->load('items.options')),
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Enums\PaymentMethod;
use App\Exceptions\EmptyCartException;
use App\Exceptions\ItemNotAvailableException;
use App\Exceptions\OrderPausedException;
use App\Exceptions\PaymentMethodNotAvailableException;
use App\Exceptions\TenantClosedException;
use App\Models\Cart;
use App\Models\Tenant;

class CheckoutValidationService
{
    // カートが空でないことを検証する
    public function validateCartNotEmpty(Cart $cart): void
    {
        if ($cart->isEmpty()) {
            throw new EmptyCartException;
        }
    }

    // テナントが営業中であることを検証する
    public function validateTenantOpen(Tenant $tenant): void
    {
        if (! $tenant->is_open) {
            throw new TenantClosedException;
        }
    }

    // テナントが注文受付を一時停止していないことを検証する
    public function validateOrderNotPaused(Tenant $tenant): void
    {
        if ($tenant->is_order_paused) {
            throw new OrderPausedException;
        }
    }

    // カート内の全商品が販売可能であることを検証する
    public function validateItemsAvailability(Cart $cart): void
    {
        $cart->load('items.menuItem');

        foreach ($cart->items as $item) {
            if (! $item->menuItem->isAvailableNow()) {
                throw new ItemNotAvailableException($item->menuItem);
            }
        }
    }

    // 決済方法が利用可能であることを検証する
    public function validatePaymentMethod(Tenant $tenant, PaymentMethod $paymentMethod): void
    {
        // 停止中テナントへの決済を防ぎ、売上・返金トラブルを回避する
        if (! $tenant->is_active) {
            throw new PaymentMethodNotAvailableException(
                $paymentMethod,
                'このテナントは現在利用できません。'
            );
        }
    }

    // チェックアウトに必要な全てのバリデーションを実行する
    public function validateForCheckout(Cart $cart, PaymentMethod $paymentMethod): void
    {
        $this->validateCartNotEmpty($cart);
        $this->validateTenantOpen($cart->tenant);
        $this->validateOrderNotPaused($cart->tenant);
        $this->validateItemsAvailability($cart);
        $this->validatePaymentMethod($cart->tenant, $paymentMethod);
    }
}

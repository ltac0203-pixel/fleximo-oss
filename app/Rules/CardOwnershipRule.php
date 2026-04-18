<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Cart;
use App\Models\FincodeCard;
use App\Models\Scopes\TenantScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CardOwnershipRule implements ValidationRule
{
    public function __construct(
        private readonly ?int $cartId = null,
        private readonly ?int $tenantId = null
    ) {}

    // バリデーションを実行する
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $authId = auth()->user()?->getAuthIdentifier();
        $authUserId = is_numeric($authId) ? (int) $authId : null;

        $card = FincodeCard::with('fincodeCustomer')->find($value);

        if (! $card) {
            return;
        }

        $fincodeCustomer = $card->fincodeCustomer;

        if (! $fincodeCustomer) {
            $fail('このカードにアクセスする権限がありません。');

            return;
        }

        if ($authUserId === null || $fincodeCustomer->user_id !== $authUserId) {
            $fail('このカードにアクセスする権限がありません。');

            return;
        }

        if ($this->tenantId !== null && $fincodeCustomer->tenant_id !== $this->tenantId) {
            $fail('このカードは当店舗で使用できません。');

            return;
        }

        if ($this->cartId !== null) {
            $cart = Cart::withoutGlobalScope(TenantScope::class)->find($this->cartId);

            if ($cart) {
                // cart.tenant_idはDB制約で必須のため、常に存在する
                if ($fincodeCustomer->tenant_id !== $cart->tenant_id) {
                    $fail('このカードは当店舗で使用できません。');
                }
            }
        }
    }
}

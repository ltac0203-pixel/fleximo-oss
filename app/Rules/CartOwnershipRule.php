<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Cart;
use App\Models\Scopes\TenantScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CartOwnershipRule implements ValidationRule
{
    // バリデーションを実行する
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $authId = auth()->user()?->getAuthIdentifier();
        $authUserId = is_numeric($authId) ? (int) $authId : null;

        $cart = Cart::withoutGlobalScope(TenantScope::class)->find($value);

        if ($cart && ($authUserId === null || $cart->user_id !== $authUserId)) {
            $fail('このカートにアクセスする権限がありません。');
        }
    }
}

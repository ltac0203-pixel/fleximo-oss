<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CartItem;
use App\Models\User;

class CartItemPolicy
{
    // ユーザーがカート商品を表示できるか
    public function view(User $user, CartItem $cartItem): bool
    {
        return $user->isCustomer() && $user->id === $cartItem->cart->user_id;
    }

    // ユーザーがカート商品を更新できるか
    public function update(User $user, CartItem $cartItem): bool
    {
        return $this->view($user, $cartItem);
    }

    // ユーザーがカート商品を削除できるか
    public function delete(User $user, CartItem $cartItem): bool
    {
        return $this->view($user, $cartItem);
    }
}

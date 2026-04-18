<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;

class CartPolicy
{
    // ユーザーがカートを表示できるか
    public function view(User $user, Cart $cart): bool
    {
        return $user->isCustomer() && $user->id === $cart->user_id;
    }

    // ユーザーがカートを更新できるか
    public function update(User $user, Cart $cart): bool
    {
        return $this->view($user, $cart);
    }

    // ユーザーがカートを削除できるか
    public function delete(User $user, Cart $cart): bool
    {
        return $this->view($user, $cart);
    }

    // ユーザーがカートに商品を追加できるか
    public function addItem(User $user, Cart $cart): bool
    {
        return $this->view($user, $cart);
    }

    // ユーザーがカートをチェックアウトできるか
    public function checkout(User $user, Cart $cart): bool
    {
        return $this->view($user, $cart);
    }
}

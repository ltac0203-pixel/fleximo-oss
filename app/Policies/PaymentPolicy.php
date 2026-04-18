<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    // ユーザーが決済情報を参照できるか
    public function view(User $user, Payment $payment): bool
    {
        return $user->isCustomer()
            && $payment->order
            && $user->id === $payment->order->user_id;
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cart> */
class CartFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'tenant_id' => Tenant::factory(),
        ];
    }

    // 特定のユーザーを設定する。
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    // 特定のテナントを設定する。
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FincodeCustomer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FincodeCustomer> */
class FincodeCustomerFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
            'fincode_customer_id' => 'c_test_'.fake()->uuid(),
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

    // 特定のfincode顧客IDを設定する。
    public function withFincodeCustomerId(string $fincodeCustomerId): static
    {
        return $this->state(fn (array $attributes) => [
            'fincode_customer_id' => $fincodeCustomerId,
        ]);
    }
}

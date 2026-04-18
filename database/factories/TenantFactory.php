<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'address' => fake()->address(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => 'active',
            'is_active' => true,
            'is_approved' => true,
            'fincode_shop_id' => null,
            'platform_fee_rate_bps' => null,
        ];
    }

    // テナントを無効状態として設定する。
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    // テナントを停止状態として設定する。
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}

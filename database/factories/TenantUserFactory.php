<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantUserRole;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantUser> */
class TenantUserFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory()->tenantStaff(),
            'role' => TenantUserRole::Staff,
        ];
    }

    // テナントユーザーを管理者として設定する。
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->tenantAdmin(),
            'role' => TenantUserRole::Admin,
        ]);
    }

    // テナントユーザーをスタッフとして設定する。
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->tenantStaff(),
            'role' => TenantUserRole::Staff,
        ]);
    }
}

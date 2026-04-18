<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    // ファクトリで使用する現在のパスワード。
    protected static ?string $password;

    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',  // Userモデルのcastsで自動ハッシュ化される
            'remember_token' => Str::random(10),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    // モデルのメールアドレスを未確認状態にする。
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    // ユーザーを管理者として設定する。
    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->role = UserRole::Admin;
            $user->saveQuietly();
        });
    }

    // ユーザーをテナント管理者として設定する。
    public function tenantAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->role = UserRole::TenantAdmin;
            $user->saveQuietly();
        });
    }

    // ユーザーをテナントスタッフとして設定する。
    public function tenantStaff(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->role = UserRole::TenantStaff;
            $user->saveQuietly();
        });
    }

    // ユーザーを顧客として設定する。
    public function customer(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->role = UserRole::Customer;
            $user->saveQuietly();
        });
    }

    // ユーザーを無効状態として設定する。
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

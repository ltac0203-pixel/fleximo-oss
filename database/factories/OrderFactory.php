<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'tenant_id' => Tenant::factory(),
            'order_code' => $this->generateOrderCode(),
            'business_date' => now()->toDateString(),
            'status' => OrderStatus::PendingPayment,
            'total_amount' => fake()->numberBetween(500, 10000),
            'payment_id' => null,
        ];
    }

    // ランダムな注文コードを生成する（A123形式）。
    // 紛らわしいアルファベット（O, I, L）を除外
    private function generateOrderCode(): string
    {
        $charset = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $letter = $charset[fake()->numberBetween(0, strlen($charset) - 1)];
        $number = str_pad((string) fake()->numberBetween(0, 999), 3, '0', STR_PAD_LEFT);

        return $letter.$number;
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

    // 特定の注文コードを設定する。
    public function withOrderCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'order_code' => $code,
        ]);
    }

    // 特定の営業日を設定する。
    public function forBusinessDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'business_date' => $date,
        ]);
    }

    // 注文を支払い待ちに設定する。
    public function pendingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PendingPayment,
        ]);
    }

    // 注文を支払い済みに設定する。
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    // 注文を受付済みに設定する。
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Accepted,
            'paid_at' => now()->subMinutes(5),
            'accepted_at' => now(),
        ]);
    }

    // 注文を調理中に設定する。
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::InProgress,
            'paid_at' => now()->subMinutes(10),
            'accepted_at' => now()->subMinutes(5),
            'in_progress_at' => now(),
        ]);
    }

    // 注文を受け渡し準備完了に設定する。
    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Ready,
            'paid_at' => now()->subMinutes(15),
            'accepted_at' => now()->subMinutes(10),
            'in_progress_at' => now()->subMinutes(5),
            'ready_at' => now(),
        ]);
    }

    // 注文を完了済みに設定する。
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Completed,
            'paid_at' => now()->subMinutes(20),
            'accepted_at' => now()->subMinutes(15),
            'in_progress_at' => now()->subMinutes(10),
            'ready_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }

    // 注文をキャンセル済みに設定する。
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'paid_at' => now()->subMinutes(10),
            'cancelled_at' => now(),
        ]);
    }

    // 注文を支払い失敗に設定する。
    public function paymentFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PaymentFailed,
        ]);
    }

    // 注文を返金済みに設定する。
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Refunded,
            'paid_at' => now()->subMinutes(30),
            'cancelled_at' => now()->subMinutes(10),
        ]);
    }

    // 特定の合計金額を設定する。
    public function totalAmount(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => $amount,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'tenant_id' => Tenant::factory(),
            'provider' => 'fincode',
            'method' => PaymentMethod::Card,
            'fincode_id' => null,
            'status' => PaymentStatus::Pending,
            'amount' => fake()->numberBetween(500, 10000),
        ];
    }

    // 特定の注文を設定する。
    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'amount' => $order->total_amount,
        ]);
    }

    // 特定のテナントを設定する。
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    // 決済方法をカードに設定する。
    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::Card,
        ]);
    }

    // 決済方法をPayPayに設定する。
    public function paypay(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::PayPay,
        ]);
    }

    // ステータスを保留に設定する。
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
        ]);
    }

    // ステータスを処理中に設定する。
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Processing,
        ]);
    }

    // ステータスを完了に設定する。
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Completed,
        ]);
    }

    // ステータスを失敗に設定する。
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
        ]);
    }

    // 特定のfincode IDを設定する。
    public function withFincodeId(string $fincodeId): static
    {
        return $this->state(fn (array $attributes) => [
            'fincode_id' => $fincodeId,
        ]);
    }

    // 特定の金額を設定する。
    public function amount(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}

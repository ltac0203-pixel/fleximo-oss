<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FincodeCard;
use App\Models\FincodeCustomer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FincodeCard> */
class FincodeCardFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    public function definition(): array
    {
        return [
            'fincode_customer_id' => FincodeCustomer::factory(),
            'fincode_card_id' => 'cs_test_'.fake()->uuid(),
            'card_no_display' => '************'.fake()->numberBetween(1000, 9999),
            'brand' => fake()->randomElement(['Visa', 'Mastercard', 'JCB', 'American Express']),
            'expire' => fake()->date('ym', '+3 years'),
            'is_default' => false,
        ];
    }

    // 特定のfincode顧客を設定する。
    public function forCustomer(FincodeCustomer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'fincode_customer_id' => $customer->id,
        ]);
    }

    // 特定のfincodeカードIDを設定する。
    public function withFincodeCardId(string $fincodeCardId): static
    {
        return $this->state(fn (array $attributes) => [
            'fincode_card_id' => $fincodeCardId,
        ]);
    }

    // デフォルトカードとして設定する。
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    // 特定のカードブランドを設定する。
    public function brand(string $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand' => $brand,
        ]);
    }

    // 特定の有効期限（YYMM形式）を設定する。
    public function expire(string $expire): static
    {
        return $this->state(fn (array $attributes) => [
            'expire' => $expire,
        ]);
    }

    // 特定のカード表示番号を設定する。
    public function cardNoDisplay(string $cardNoDisplay): static
    {
        return $this->state(fn (array $attributes) => [
            'card_no_display' => $cardNoDisplay,
        ]);
    }
}

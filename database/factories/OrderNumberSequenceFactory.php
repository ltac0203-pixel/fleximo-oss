<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderNumberSequence;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderNumberSequence> */
class OrderNumberSequenceFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'business_date' => now()->toDateString(),
            'last_sequence' => 0,
        ];
    }

    // 指定したシーケンス番号を設定
    public function withSequence(int $sequence): static
    {
        return $this->state(fn (array $attributes) => [
            'last_sequence' => $sequence,
        ]);
    }

    // 指定した営業日を設定
    public function forBusinessDate(string|\Carbon\Carbon $date): static
    {
        return $this->state(fn (array $attributes) => [
            'business_date' => $date,
        ]);
    }
}

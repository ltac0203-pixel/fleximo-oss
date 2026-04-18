<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebhookLog> */
class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'provider' => 'fincode',
            'fincode_id' => 'pay_'.$this->faker->unique()->uuid(),
            'event_type' => $this->faker->randomElement([
                'payment.completed',
                'payment.failed',
                'payment.refunded',
            ]),
            'payload' => [
                'id' => $this->faker->uuid(),
                'event' => 'payment.completed',
                'amount' => $this->faker->numberBetween(100, 10000),
            ],
            'processed' => false,
            'processed_at' => null,
            'error_message' => null,
        ];
    }

    // 処理済み状態
    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed' => true,
            'processed_at' => now(),
        ]);
    }

    // エラー状態
    public function failed(string $errorMessage = 'Processing failed'): static
    {
        return $this->state(fn (array $attributes) => [
            'processed' => false,
            'error_message' => $errorMessage,
        ]);
    }

    // payment.completed イベント
    public function paymentCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'payment.completed',
            'payload' => array_merge($attributes['payload'] ?? [], [
                'event' => 'payment.completed',
            ]),
        ]);
    }

    // payment.failed イベント
    public function paymentFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'payment.failed',
            'payload' => array_merge($attributes['payload'] ?? [], [
                'event' => 'payment.failed',
                'error_code' => 'CARD_DECLINED',
            ]),
        ]);
    }

    // payment.refunded イベント
    public function paymentRefunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'payment.refunded',
            'payload' => array_merge($attributes['payload'] ?? [], [
                'event' => 'payment.refunded',
            ]),
        ]);
    }
}

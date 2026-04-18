<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BusinessType;
use App\Enums\TenantApplicationStatus;
use App\Models\TenantApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantApplication> */
class TenantApplicationFactory extends Factory
{
    // モデルのデフォルト状態を定義する。
    //
    // @return array<string, mixed>
    public function definition(): array
    {
        return [
            'applicant_name' => fake()->name(),
            'applicant_email' => fake()->unique()->safeEmail(),
            'applicant_phone' => fake()->phoneNumber(),
            'tenant_name' => fake()->company(),
            'tenant_address' => fake()->address(),
            'business_type' => BusinessType::Restaurant,
            'status' => TenantApplicationStatus::Pending,
        ];
    }

    // 申請を審査中状態として設定する。
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantApplicationStatus::UnderReview,
        ]);
    }

    // 申請を承認済み状態として設定する。
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantApplicationStatus::Approved,
        ]);
    }

    // 申請を却下状態として設定する。
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantApplicationStatus::Rejected,
            'rejection_reason' => '審査基準を満たしていないため',
        ]);
    }
}

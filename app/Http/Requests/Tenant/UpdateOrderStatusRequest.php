<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTenantAdmin() || $this->user()->isTenantStaff();
    }

    public function rules(): array
    {
        // KDS（キッチンディスプレイシステム）で更新可能なステータス
        $allowedStatuses = [
            OrderStatus::Accepted->value,
            OrderStatus::InProgress->value,
            OrderStatus::Ready->value,
            OrderStatus::Completed->value,
            OrderStatus::Cancelled->value,
        ];

        return [
            'status' => ['required', 'string', Rule::in($allowedStatuses)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'ステータスは必須です。',
            'status.in' => '指定されたステータスはKDSで更新できません。',
        ];
    }

    // バリデーション済みのステータスを取得
    public function getStatus(): OrderStatus
    {
        return OrderStatus::from($this->validated('status'));
    }
}

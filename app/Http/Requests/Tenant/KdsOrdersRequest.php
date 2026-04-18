<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\OrderStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KdsOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isTenantAdmin() || $this->user()->isTenantStaff();
    }

    public function rules(): array
    {
        return [
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['required', 'string', Rule::in(OrderStatus::values())],
            'business_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'updated_since' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'statuses.array' => 'ステータスは配列で指定してください。',
            'statuses.*.in' => '不正なステータス値が含まれています。',
            'business_date.date' => '営業日は有効な日付を指定してください。',
            'business_date.date_format' => '営業日はY-m-d形式で指定してください。',
            'updated_since.date' => '更新日時は有効な日時を指定してください。',
        ];
    }

    // バリデーション済みのステータス配列を取得
    // @return array<OrderStatus>
    public function getStatuses(): array
    {
        $statuses = $this->validated('statuses', []);

        return array_map(fn ($status) => OrderStatus::from($status), $statuses);
    }

    // バリデーション済みの更新日時を取得
    public function getUpdatedSince(): ?Carbon
    {
        $updatedSince = $this->validated('updated_since');

        return $updatedSince ? Carbon::parse($updatedSince) : null;
    }
}

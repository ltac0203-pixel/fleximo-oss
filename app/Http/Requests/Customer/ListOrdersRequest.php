<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrdersRequest extends FormRequest
{
    // 認可されるかどうかを判定
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    // バリデーションルールを取得
    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(OrderStatus::values())],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'ステータス',
            'page' => 'ページ',
            'per_page' => 'ページあたり件数',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Menu\CreateOptionData;
use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;

class StoreOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Option::class, $this->route('optionGroup')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'integer', 'min:-999999', 'max:999999'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'オプション名は必須です。',
            'name.max' => 'オプション名は255文字以内で入力してください。',
            'price.integer' => '価格は整数で入力してください。',
            'price.min' => '価格は-999,999〜999,999円の範囲で入力してください。',
            'price.max' => '価格は999,999円以内で入力してください。',
        ];
    }

    public function toDto(): CreateOptionData
    {
        $validated = $this->validated();

        return new CreateOptionData(
            name: $validated['name'],
            price: $validated['price'] ?? null,
            sort_order: $validated['sort_order'] ?? null,
            is_active: $validated['is_active'] ?? null,
        );
    }
}

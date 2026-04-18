<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Menu\CreateCategoryData;
use Illuminate\Foundation\Http\FormRequest;

class StoreMenuCategoryRequest extends FormRequest
{
    public function toDto(): CreateCategoryData
    {
        $validated = $this->validated();

        return new CreateCategoryData(
            name: $validated['name'],
            sort_order: $validated['sort_order'] ?? null,
            is_active: $validated['is_active'] ?? true,
        );
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\MenuCategory::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'カテゴリ名は必須です。',
            'name.max' => 'カテゴリ名は100文字以内で入力してください。',
        ];
    }
}

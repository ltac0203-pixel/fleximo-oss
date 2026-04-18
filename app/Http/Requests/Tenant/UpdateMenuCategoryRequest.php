<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Menu\UpdateCategoryData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuCategoryRequest extends FormRequest
{
    public function toDto(): UpdateCategoryData
    {
        $validated = $this->validated();

        return new UpdateCategoryData(
            name: $validated['name'] ?? null,
            sort_order: $validated['sort_order'] ?? null,
            is_active: $validated['is_active'] ?? null,
            presentFields: array_keys($validated),
        );
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
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

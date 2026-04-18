<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class ReorderMenuCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reorder', \App\Models\MenuCategory::class);
    }

    public function rules(): array
    {
        return [
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', 'exists:menu_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ordered_ids.required' => '並び順を指定してください。',
            'ordered_ids.array' => '並び順は配列で指定してください。',
            'ordered_ids.*.exists' => '存在しないカテゴリが含まれています。',
        ];
    }
}

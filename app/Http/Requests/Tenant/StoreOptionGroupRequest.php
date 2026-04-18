<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Menu\CreateOptionGroupData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOptionGroupRequest extends FormRequest
{
    public function toDto(): CreateOptionGroupData
    {
        $validated = $this->validated();

        return new CreateOptionGroupData(
            name: $validated['name'],
            required: $validated['required'] ?? false,
            min_select: $validated['min_select'] ?? 0,
            max_select: $validated['max_select'] ?? 1,
            sort_order: $validated['sort_order'] ?? null,
            is_active: $validated['is_active'] ?? true,
        );
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\OptionGroup::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'required' => ['nullable', 'boolean'],
            'min_select' => ['nullable', 'integer', 'min:0'],
            'max_select' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $minSelect = $this->input('min_select', 0);
            $maxSelect = $this->input('max_select', 1);

            if ($minSelect > $maxSelect) {
                $validator->errors()->add('min_select', '最小選択数は最大選択数以下にしてください。');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'オプショングループ名は必須です。',
            'name.max' => 'オプショングループ名は255文字以内で入力してください。',
            'min_select.min' => '最小選択数は0以上で入力してください。',
            'max_select.min' => '最大選択数は1以上で入力してください。',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Menu\UpdateOptionGroupData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOptionGroupRequest extends FormRequest
{
    public function toDto(): UpdateOptionGroupData
    {
        $validated = $this->validated();

        return new UpdateOptionGroupData(
            name: $validated['name'] ?? null,
            required: $validated['required'] ?? null,
            min_select: $validated['min_select'] ?? null,
            max_select: $validated['max_select'] ?? null,
            sort_order: $validated['sort_order'] ?? null,
            is_active: $validated['is_active'] ?? null,
            presentFields: array_keys($validated),
        );
    }

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('optionGroup'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
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
            $optionGroup = $this->route('optionGroup');
            $minSelect = $this->input('min_select', $optionGroup->min_select);
            $maxSelect = $this->input('max_select', $optionGroup->max_select);

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

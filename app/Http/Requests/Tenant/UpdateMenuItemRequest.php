<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Menu\UpdateMenuItemData;
use App\Models\MenuCategory;
use App\Models\OptionGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('item'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'required', 'integer', 'min:0', 'max:999999'],
            'category_ids' => ['sometimes', 'required', 'array', 'min:1', 'max:3'],
            'category_ids.*' => ['required', 'integer', 'exists:menu_categories,id'],
            'option_group_ids' => ['sometimes', 'array'],
            'option_group_ids.*' => ['required', 'integer', 'exists:option_groups,id'],
            'is_active' => ['nullable', 'boolean'],
            'is_sold_out' => ['nullable', 'boolean'],
            'available_from' => ['nullable', 'date_format:H:i'],
            'available_until' => ['nullable', 'date_format:H:i'],
            'available_days' => ['nullable', 'integer', 'min:0', 'max:127'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'allergens' => ['nullable', 'integer', 'min:0', 'max:255'],
            'allergen_advisories' => ['nullable', 'integer', 'min:0', 'max:1048575'],
            'allergen_note' => ['nullable', 'string', 'max:500'],
            'nutrition_info' => ['nullable', 'array'],
            'nutrition_info.energy' => ['nullable', 'numeric', 'min:0'],
            'nutrition_info.protein' => ['nullable', 'numeric', 'min:0'],
            'nutrition_info.fat' => ['nullable', 'numeric', 'min:0'],
            'nutrition_info.carbohydrate' => ['nullable', 'numeric', 'min:0'],
            'nutrition_info.salt' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tenantId = $this->user()->getTenantId();

            if ($this->has('category_ids')) {
                $validCategoryIds = MenuCategory::where('tenant_id', $tenantId)
                    ->whereIn('id', $this->input('category_ids'))
                    ->pluck('id')
                    ->toArray();

                if (count($validCategoryIds) !== count($this->input('category_ids'))) {
                    $validator->errors()->add('category_ids', '指定されたカテゴリが見つかりません。');
                }
            }

            if ($this->has('option_group_ids')) {
                $validOptionGroupIds = OptionGroup::where('tenant_id', $tenantId)
                    ->whereIn('id', $this->input('option_group_ids'))
                    ->pluck('id')
                    ->toArray();

                if (count($validOptionGroupIds) !== count($this->input('option_group_ids'))) {
                    $validator->errors()->add('option_group_ids', '指定されたオプショングループが見つかりません。');
                }
            }
        });
    }

    public function toDto(): UpdateMenuItemData
    {
        $validated = $this->validated();
        $presentFields = array_keys($validated);

        return new UpdateMenuItemData(
            name: $validated['name'] ?? null,
            price: $validated['price'] ?? null,
            category_ids: $validated['category_ids'] ?? null,
            option_group_ids: $validated['option_group_ids'] ?? null,
            description: $validated['description'] ?? null,
            is_active: $validated['is_active'] ?? null,
            is_sold_out: $validated['is_sold_out'] ?? null,
            available_from: $validated['available_from'] ?? null,
            available_until: $validated['available_until'] ?? null,
            available_days: $validated['available_days'] ?? null,
            sort_order: $validated['sort_order'] ?? null,
            allergens: $validated['allergens'] ?? null,
            allergen_advisories: $validated['allergen_advisories'] ?? null,
            allergen_note: $validated['allergen_note'] ?? null,
            nutrition_info: $validated['nutrition_info'] ?? null,
            presentFields: $presentFields,
        );
    }

    public function messages(): array
    {
        return [
            'name.required' => '商品名は必須です。',
            'name.max' => '商品名は255文字以内で入力してください。',
            'description.max' => '説明は1000文字以内で入力してください。',
            'price.required' => '価格は必須です。',
            'price.integer' => '価格は整数で入力してください。',
            'price.min' => '価格は0以上で入力してください。',
            'price.max' => '価格は999,999円以内で入力してください。',
            'category_ids.required' => 'カテゴリを1つ以上選択してください。',
            'category_ids.min' => 'カテゴリを1つ以上選択してください。',
            'category_ids.max' => 'カテゴリは最大3つまで選択できます。',
            'option_group_ids.array' => 'オプショングループの形式が正しくありません。',
            'option_group_ids.*.integer' => 'オプショングループIDは整数で指定してください。',
            'option_group_ids.*.exists' => '指定されたオプショングループが存在しません。',
            'available_from.date_format' => '提供開始時刻はHH:MM形式で入力してください。',
            'available_until.date_format' => '提供終了時刻はHH:MM形式で入力してください。',
            'available_days.min' => '提供曜日は0以上で入力してください。',
            'available_days.max' => '提供曜日は127以下で入力してください。',
            'allergens.max' => 'アレルゲン値が不正です。',
            'allergen_advisories.max' => 'アレルゲン推奨表示値が不正です。',
            'allergen_note.max' => 'アレルゲン備考は500文字以内で入力してください。',
            'nutrition_info.array' => '栄養成分の形式が正しくありません。',
            'nutrition_info.energy.numeric' => 'エネルギーは数値で入力してください。',
            'nutrition_info.energy.min' => 'エネルギーは0以上で入力してください。',
            'nutrition_info.protein.numeric' => 'たんぱく質は数値で入力してください。',
            'nutrition_info.protein.min' => 'たんぱく質は0以上で入力してください。',
            'nutrition_info.fat.numeric' => '脂質は数値で入力してください。',
            'nutrition_info.fat.min' => '脂質は0以上で入力してください。',
            'nutrition_info.carbohydrate.numeric' => '炭水化物は数値で入力してください。',
            'nutrition_info.carbohydrate.min' => '炭水化物は0以上で入力してください。',
            'nutrition_info.salt.numeric' => '食塩相当量は数値で入力してください。',
            'nutrition_info.salt.min' => '食塩相当量は0以上で入力してください。',
        ];
    }
}

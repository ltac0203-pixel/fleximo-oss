<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCartItemRequest extends FormRequest
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
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:99'],
            'option_ids' => ['sometimes', 'array'],
            'option_ids.*' => ['integer', 'exists:options,id'],
        ];
    }

    // バリデーションの後処理
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // バリデーションエラーがすでにあれば追加チェックをスキップ
            if ($validator->errors()->any()) {
                return;
            }

            // オプションが指定されている場合、商品に紐付いているか確認
            $optionIds = $this->input('option_ids');
            if ($optionIds !== null) {
                $cartItem = $this->route('cartItem');
                if ($cartItem) {
                    $menuItem = $cartItem->menuItem;
                    $menuItem->load('optionGroups.options');
                    $validOptionIds = $menuItem->optionGroups
                        ->flatMap(fn ($group) => $group->options->pluck('id'))
                        ->toArray();

                    $invalidIds = array_diff($optionIds, $validOptionIds);
                    if (! empty($invalidIds)) {
                        $validator->errors()->add('option_ids', 'この商品に紐付いていないオプションが選択されています。');
                    }
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'quantity' => '数量',
            'option_ids' => 'オプション',
            'option_ids.*' => 'オプションID',
        ];
    }
}

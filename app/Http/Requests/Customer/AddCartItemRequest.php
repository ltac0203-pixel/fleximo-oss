<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\MenuItem;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddCartItemRequest extends FormRequest
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
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'option_ids' => ['array'],
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

            $tenantId = $this->input('tenant_id');
            $menuItemId = $this->input('menu_item_id');

            // テナントがアクティブか確認
            $tenant = Tenant::find($tenantId);
            if (! $tenant || ! $tenant->is_active) {
                $validator->errors()->add('tenant_id', '指定されたテナントは現在利用できません。');

                return;
            }

            // メニュー商品がそのテナントに属しているか確認
            $menuItem = MenuItem::where('id', $menuItemId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $menuItem) {
                $validator->errors()->add('menu_item_id', '指定された商品はこのテナントに存在しません。');

                return;
            }

            // オプションが指定されている場合、商品に紐付いているか確認
            $optionIds = $this->input('option_ids');
            if ($optionIds !== null && count($optionIds) > 0) {
                $menuItem->load('optionGroups.options');
                $validOptionIds = $menuItem->optionGroups
                    ->flatMap(fn ($group) => $group->options->pluck('id'))
                    ->toArray();

                $invalidIds = array_diff($optionIds, $validOptionIds);
                if (! empty($invalidIds)) {
                    $validator->errors()->add('option_ids', 'この商品に紐付いていないオプションが選択されています。');
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'tenant_id' => 'テナントID',
            'menu_item_id' => '商品ID',
            'quantity' => '数量',
            'option_ids' => 'オプション',
            'option_ids.*' => 'オプションID',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Models\OptionGroup;
use App\Models\Scopes\TenantScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttachOptionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageOptionGroups', $this->route('item'));
    }

    public function rules(): array
    {
        return [
            'option_group_id' => ['required', 'integer', 'exists:option_groups,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->has('option_group_id')) {
                $tenantId = $this->user()->getTenantId();
                // TenantScopeを無視して検索（existsルールは通過済み）
                $optionGroup = OptionGroup::withoutGlobalScope(TenantScope::class)->find($this->input('option_group_id'));

                if ($optionGroup && $optionGroup->tenant_id !== $tenantId) {
                    $validator->errors()->add('option_group_id', '指定されたオプショングループが見つかりません。');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'option_group_id.required' => 'オプショングループIDは必須です。',
            'option_group_id.exists' => '指定されたオプショングループが見つかりません。',
        ];
    }
}

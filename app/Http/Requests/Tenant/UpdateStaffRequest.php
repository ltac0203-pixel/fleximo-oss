<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Staff\UpdateStaffData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    // リクエストを実行できるか判定する
    public function authorize(): bool
    {
        $staff = $this->route('staff');

        return $this->user()->can('update', $staff);
    }

    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        $staffId = $this->route('staff')->id;

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($staffId),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    // バリデーション済みデータからDTOを生成する
    public function toDto(): UpdateStaffData
    {
        $validated = $this->validated();

        return new UpdateStaffData(
            name: $validated['name'] ?? null,
            email: $validated['email'] ?? null,
            phone: $validated['phone'] ?? null,
            is_active: isset($validated['is_active']) ? (bool) $validated['is_active'] : null,
            password: $validated['password'] ?? null,
            presentFields: array_keys($validated),
        );
    }

    // バリデーションエラーメッセージをカスタマイズする
    public function messages(): array
    {
        return [
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => 'パスワード確認が一致しません。',
        ];
    }
}

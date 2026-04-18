<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Staff\CreateStaffData;
use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    // リクエストを実行できるか判定する
    public function authorize(): bool
    {
        return $this->user()->isTenantAdmin();
    }

    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    // バリデーション済みデータからDTOを生成する
    public function toDto(): CreateStaffData
    {
        $validated = $this->validated();

        return new CreateStaffData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            phone: $validated['phone'] ?? null,
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

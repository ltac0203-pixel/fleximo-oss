<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\Tenant\CreateTenantApplicationWithUserData;
use App\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class TenantApplicationRequest extends FormRequest
{
    // 公開フォーム: 認証不要。throttleミドルウェアで保護済み。
    public function authorize(): bool
    {
        return true;
    }

    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        return [
            'applicant_name' => ['required', 'string', 'max:100'],
            'applicant_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'applicant_phone' => ['required', 'string', 'max:20'],
            'tenant_name' => ['required', 'string', 'max:100'],
            'tenant_address' => ['nullable', 'string', 'max:255'],
            'business_type' => ['required', Rule::enum(BusinessType::class)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'password_confirmation' => ['required'],
            'website' => ['nullable', 'max:0'], // ハニーポット（スパムbot対策）
        ];
    }

    public function messages(): array
    {
        return [
            'applicant_name.required' => 'お名前を入力してください',
            'applicant_name.max' => 'お名前は100文字以内で入力してください',
            'applicant_email.required' => 'メールアドレスを入力してください',
            'applicant_email.email' => '有効なメールアドレスを入力してください',
            'applicant_email.unique' => 'このメールアドレスは既に登録されています',
            'applicant_phone.required' => '電話番号を入力してください',
            'applicant_phone.max' => '電話番号は20文字以内で入力してください',
            'tenant_name.required' => '店舗名を入力してください',
            'tenant_name.max' => '店舗名は100文字以内で入力してください',
            'tenant_address.max' => '住所は255文字以内で入力してください',
            'business_type.required' => '業種を選択してください',
            'business_type' => '無効な業種です',
            'password.required' => 'パスワードを入力してください',
            'password.confirmed' => 'パスワードが一致しません',
            'password_confirmation.required' => 'パスワード（確認）を入力してください',
        ];
    }

    public function toDto(): CreateTenantApplicationWithUserData
    {
        $validated = $this->validated();

        return new CreateTenantApplicationWithUserData(
            applicant_name: $validated['applicant_name'],
            applicant_email: $validated['applicant_email'],
            applicant_phone: $validated['applicant_phone'],
            tenant_name: $validated['tenant_name'],
            business_type: $validated['business_type'],
            password: $validated['password'],
            tenant_address: $validated['tenant_address'] ?? null,
        );
    }

    public function attributes(): array
    {
        return [
            'applicant_name' => 'お名前',
            'applicant_email' => 'メールアドレス',
            'applicant_phone' => '電話番号',
            'tenant_name' => '店舗名',
            'tenant_address' => '住所',
            'business_type' => '業種',
            'password' => 'パスワード',
            'password_confirmation' => 'パスワード（確認）',
        ];
    }
}

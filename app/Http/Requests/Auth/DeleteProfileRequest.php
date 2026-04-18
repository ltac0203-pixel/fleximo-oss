<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DeleteProfileRequest extends FormRequest
{
    // プロフィール削除は顧客のみ許可。管理者・テナント管理者・スタッフの自己削除を防止。
    public function authorize(): bool
    {
        return $this->user()->isCustomer();
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }
}

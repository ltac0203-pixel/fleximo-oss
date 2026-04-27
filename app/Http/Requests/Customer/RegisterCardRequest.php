<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCardRequest extends FormRequest
{
    // ユーザーがこのリクエストを実行可能か判定する。
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => __('validation.custom.token_required'),
            'token.string' => __('validation.custom.token_string'),
        ];
    }
}

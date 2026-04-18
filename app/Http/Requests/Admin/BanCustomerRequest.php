<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class BanCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin.customer.ban', $this->route('customer'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'BAN理由を入力してください。',
            'reason.max' => 'BAN理由は1000文字以内で入力してください。',
        ];
    }
}

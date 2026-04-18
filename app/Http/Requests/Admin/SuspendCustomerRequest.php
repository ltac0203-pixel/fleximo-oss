<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SuspendCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin.customer.suspend', $this->route('customer'));
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
            'reason.required' => '停止理由を入力してください。',
            'reason.max' => '停止理由は1000文字以内で入力してください。',
        ];
    }
}

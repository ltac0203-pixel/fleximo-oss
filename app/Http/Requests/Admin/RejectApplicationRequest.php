<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectApplicationRequest extends FormRequest
{
    // ユーザーがこのリクエストを実行可能か判定する。
    public function authorize(): bool
    {
        return $this->user()->can('reject', $this->route('application'));
    }

    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => '却下理由を入力してください',
            'rejection_reason.max' => '却下理由は2000文字以内で入力してください',
        ];
    }
}

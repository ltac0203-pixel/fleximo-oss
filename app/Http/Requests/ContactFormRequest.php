<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 公開フォーム: 認証不要。throttleミドルウェアで保護済み。
        return true;
    }

    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/\A[^\r\n]*\z/'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:200', 'regex:/\A[^\r\n]*\z/'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['nullable', 'max:0'], // ハニーポット（値があればスパム）
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'お名前を入力してください。',
            'name.max' => 'お名前は100文字以内で入力してください。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'subject.required' => '件名を入力してください。',
            'subject.max' => '件名は200文字以内で入力してください。',
            'message.required' => 'お問い合わせ内容を入力してください。',
            'message.max' => 'お問い合わせ内容は5000文字以内で入力してください。',
        ];
    }
}

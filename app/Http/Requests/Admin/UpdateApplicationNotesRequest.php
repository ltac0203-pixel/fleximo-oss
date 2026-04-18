<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationNotesRequest extends FormRequest
{
    // ユーザーがこのリクエストを実行可能か判定する。
    public function authorize(): bool
    {
        return $this->user()->can('updateNotes', $this->route('application'));
    }

    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        return [
            'internal_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'internal_notes.string' => '内部メモは文字列で入力してください',
            'internal_notes.max' => '内部メモは10000文字以内で入力してください',
        ];
    }
}

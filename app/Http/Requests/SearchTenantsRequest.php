<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// テナント検索リクエスト
class SearchTenantsRequest extends FormRequest
{
    // 公開エンドポイント: 認証不要。
    public function authorize(): bool
    {
        return true;
    }

    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'query' => '検索キーワード',
            'lat' => '緯度',
            'lng' => '経度',
            'per_page' => 'ページサイズ',
        ];
    }
}

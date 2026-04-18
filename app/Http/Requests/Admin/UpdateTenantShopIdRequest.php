<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateTenantShopIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin.access');
    }

    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        return [
            'fincode_shop_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}

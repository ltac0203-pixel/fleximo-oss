<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ExportCustomerDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin.customer.export', $this->route('customer'));
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'string', Rule::in(['json', 'csv'])],
        ];
    }

    public function messages(): array
    {
        return [
            'format.required' => 'エクスポート形式を選択してください。',
            'format.in' => 'エクスポート形式はJSONまたはCSVを選択してください。',
        ];
    }
}

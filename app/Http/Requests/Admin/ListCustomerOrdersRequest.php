<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ListCustomerOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin.customer.view', $this->route('customer'));
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'order_status' => ['nullable', 'string', Rule::in(OrderStatus::values())],
        ];
    }
}

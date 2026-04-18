<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AccountStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ListCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin.customer.viewAny');
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(AccountStatus::values())],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(['name', 'email', 'created_at', 'last_login_at'])],
            'sort_dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}

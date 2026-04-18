<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\TenantApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ListApplicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('admin.access');
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(array_column(TenantApplicationStatus::cases(), 'value'))],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(['application_code', 'tenant_name', 'applicant_name', 'status', 'created_at'])],
            'sort_dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}

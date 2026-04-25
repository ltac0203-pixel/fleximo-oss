<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Concerns\HasDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DashboardPaymentMethodsRequest extends FormRequest
{
    use HasDateRange;

    public function authorize(): bool
    {
        return Gate::allows('dashboard.view');
    }

    public function rules(): array
    {
        return $this->dateRangeRules();
    }

    public function messages(): array
    {
        return $this->dateRangeMessages();
    }
}

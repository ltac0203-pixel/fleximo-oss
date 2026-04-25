<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Concerns\HasDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DashboardExportRequest extends FormRequest
{
    use HasDateRange;

    public function authorize(): bool
    {
        return Gate::allows('dashboard.exportCsv');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return $this->dateRangeRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->dateRangeMessages();
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [$this->dateRangeMaxDaysCallback()];
    }
}

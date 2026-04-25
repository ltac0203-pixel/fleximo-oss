<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\SalesPeriod;
use App\Http\Requests\Concerns\HasDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;

class DashboardSalesRequest extends FormRequest
{
    use HasDateRange;

    public function authorize(): bool
    {
        return Gate::allows('dashboard.view');
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'string', new Enum(SalesPeriod::class)],
            ...$this->dateRangeRules(),
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->dateRangeMessages(),
            'period.required' => '期間を指定してください。',
            'period.in' => '期間はdaily、weekly、monthlyのいずれかを指定してください。',
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [$this->dateRangeMaxDaysCallback()];
    }

    public function getPeriod(): SalesPeriod
    {
        return SalesPeriod::from($this->validated('period'));
    }
}

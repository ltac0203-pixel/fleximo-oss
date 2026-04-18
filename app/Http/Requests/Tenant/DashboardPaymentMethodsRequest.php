<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DashboardPaymentMethodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('dashboard.view');
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => '開始日を指定してください。',
            'start_date.date' => '開始日は有効な日付を指定してください。',
            'start_date.date_format' => '開始日はY-m-d形式で指定してください。',
            'end_date.required' => '終了日を指定してください。',
            'end_date.date' => '終了日は有効な日付を指定してください。',
            'end_date.date_format' => '終了日はY-m-d形式で指定してください。',
            'end_date.after_or_equal' => '終了日は開始日以降を指定してください。',
        ];
    }

    public function getStartDate(): Carbon
    {
        return Carbon::parse($this->validated('start_date'));
    }

    public function getEndDate(): Carbon
    {
        return Carbon::parse($this->validated('end_date'));
    }
}

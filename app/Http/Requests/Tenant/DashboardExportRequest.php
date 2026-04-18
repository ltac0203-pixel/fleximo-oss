<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class DashboardExportRequest extends FormRequest
{
    private const MAX_RANGE_DAYS = 366;

    public function authorize(): bool
    {
        return Gate::allows('dashboard.exportCsv');
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $startDateInput = $this->input('start_date');
                $endDateInput = $this->input('end_date');
                if (! is_string($startDateInput) || ! is_string($endDateInput)) {
                    return;
                }

                try {
                    $startDate = Carbon::createFromFormat('Y-m-d', $startDateInput)->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', $endDateInput)->startOfDay();
                } catch (\Throwable) {
                    return;
                }

                if ($startDate->diffInDays($endDate) > self::MAX_RANGE_DAYS) {
                    $validator->errors()->add('end_date', '期間は366日以内で指定してください。');
                }
            },
        ];
    }

    public function getStartDate(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->validated('start_date'))->startOfDay();
    }

    public function getEndDate(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->validated('end_date'))->startOfDay();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class AdminRevenueDashboardRequest extends FormRequest
{
    private const DEFAULT_RANGE_DAYS = 30;

    private const MAX_RANGE_DAYS = 366;

    private const DEFAULT_RANKING_LIMIT = 10;

    public function authorize(): bool
    {
        return Gate::allows('admin.access');
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'ranking_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date' => '開始日は有効な日付を指定してください。',
            'start_date.date_format' => '開始日はY-m-d形式で指定してください。',
            'end_date.date' => '終了日は有効な日付を指定してください。',
            'end_date.date_format' => '終了日はY-m-d形式で指定してください。',
            'end_date.after_or_equal' => '終了日は開始日以降を指定してください。',
            'ranking_limit.integer' => 'ランキング件数は整数で指定してください。',
            'ranking_limit.min' => 'ランキング件数は1以上で指定してください。',
            'ranking_limit.max' => 'ランキング件数は50以下で指定してください。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $start = $this->resolvedStartDate();
            $end = $this->resolvedEndDate();

            if ($start->gt($end)) {
                $validator->errors()->add('end_date', '終了日は開始日以降を指定してください。');
            }

            if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
                $validator->errors()->add(
                    'end_date',
                    '集計期間は367日以内で指定してください。'
                );
            }
        });
    }

    public function getStartDate(): Carbon
    {
        return $this->resolvedStartDate();
    }

    public function getEndDate(): Carbon
    {
        return $this->resolvedEndDate();
    }

    public function getRankingLimit(): int
    {
        return (int) ($this->validated('ranking_limit') ?? self::DEFAULT_RANKING_LIMIT);
    }

    private function resolvedStartDate(): Carbon
    {
        $start = $this->input('start_date');
        if ($start !== null && $start !== '') {
            return Carbon::parse($start)->startOfDay();
        }

        return $this->resolvedEndDate()->copy()->subDays(self::DEFAULT_RANGE_DAYS - 1)->startOfDay();
    }

    private function resolvedEndDate(): Carbon
    {
        $end = $this->input('end_date');
        if ($end !== null && $end !== '') {
            return Carbon::parse($end)->startOfDay();
        }

        return Carbon::today()->startOfDay();
    }
}

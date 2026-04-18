<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\SalesPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;

class DashboardSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('dashboard.view');
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'string', new Enum(SalesPeriod::class)],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $startDate = $this->input('start_date');
                    if ($startDate && $value) {
                        $start = Carbon::parse($startDate);
                        $end = Carbon::parse($value);
                        if ($start->diffInDays($end) > 366) {
                            $fail('日付範囲は最大1年（366日）以内で指定してください。');
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => '期間を指定してください。',
            'period.in' => '期間はdaily、weekly、monthlyのいずれかを指定してください。',
            'start_date.required' => '開始日を指定してください。',
            'start_date.date' => '開始日は有効な日付を指定してください。',
            'start_date.date_format' => '開始日はY-m-d形式で指定してください。',
            'end_date.required' => '終了日を指定してください。',
            'end_date.date' => '終了日は有効な日付を指定してください。',
            'end_date.date_format' => '終了日はY-m-d形式で指定してください。',
            'end_date.after_or_equal' => '終了日は開始日以降を指定してください。',
        ];
    }

    // バリデーション済みの期間タイプを取得
    public function getPeriod(): SalesPeriod
    {
        return SalesPeriod::from($this->validated('period'));
    }

    // バリデーション済みの開始日を取得
    public function getStartDate(): Carbon
    {
        return Carbon::parse($this->validated('start_date'));
    }

    // バリデーション済みの終了日を取得
    public function getEndDate(): Carbon
    {
        return Carbon::parse($this->validated('end_date'));
    }
}

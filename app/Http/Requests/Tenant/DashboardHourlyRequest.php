<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DashboardHourlyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('dashboard.view');
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.date' => '日付は有効な日付を指定してください。',
            'date.date_format' => '日付はY-m-d形式で指定してください。',
        ];
    }

    // バリデーション済みの日付を取得（デフォルト: 今日）
    public function getDate(): Carbon
    {
        $date = $this->validated('date');

        return $date ? Carbon::parse($date) : Carbon::today();
    }
}

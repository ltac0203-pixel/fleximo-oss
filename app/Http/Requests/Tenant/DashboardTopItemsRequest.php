<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\TopItemsPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DashboardTopItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('dashboard.view');
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::enum(TopItemsPeriod::class)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.enum' => '期間はweek、month、yearのいずれかを指定してください。',
            'limit.integer' => '件数は整数で指定してください。',
            'limit.min' => '件数は1以上を指定してください。',
            'limit.max' => '件数は50以下を指定してください。',
        ];
    }

    // バリデーション済みの期間タイプを取得（デフォルト: month）
    public function getPeriod(): TopItemsPeriod
    {
        $value = $this->validated('period');

        return $value !== null
            ? TopItemsPeriod::from($value)
            : TopItemsPeriod::Month;
    }

    // バリデーション済みの件数上限を取得（デフォルト: 10）
    public function getLimit(): int
    {
        return (int) ($this->validated('limit') ?? 10);
    }
}

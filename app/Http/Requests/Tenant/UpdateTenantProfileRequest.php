<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\DTOs\Tenant\BusinessHourData;
use App\DTOs\Tenant\UpdateTenantProfileData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

// テナントプロフィール更新リクエスト
class UpdateTenantProfileRequest extends FormRequest
{
    // ユーザーがこのリクエストを実行する権限があるか判定する
    public function authorize(): bool
    {
        return Gate::allows('tenant.manage');
    }

    // @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'business_hours' => ['sometimes', 'array'],
            'business_hours.*' => ['array'],
            'business_hours.*.weekday' => ['required', 'integer', 'between:0,6'],
            'business_hours.*.open_time' => ['required', 'date_format:H:i'],
            'business_hours.*.close_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function attributes(): array
    {
        return [
            'business_hours' => '営業時間',
            'business_hours.*.weekday' => '営業曜日',
            'business_hours.*.open_time' => '開店時間',
            'business_hours.*.close_time' => '閉店時間',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hours = $this->input('business_hours', []);
            foreach ($hours as $index => $hour) {
                $open = $hour['open_time'] ?? null;
                $close = $hour['close_time'] ?? null;
                if ($open && $close && $open === $close) {
                    $validator->errors()->add(
                        "business_hours.{$index}.close_time",
                        '閉店時間は開店時間と異なる時刻を指定してください。'
                    );
                }
            }
        });
    }

    public function toDto(): UpdateTenantProfileData
    {
        $validated = $this->validated();

        $businessHours = null;
        if (array_key_exists('business_hours', $validated)) {
            $businessHours = array_map(
                fn (array $hour) => new BusinessHourData(
                    weekday: (int) $hour['weekday'],
                    open_time: $hour['open_time'],
                    close_time: $hour['close_time'],
                ),
                $validated['business_hours']
            );
        }

        return new UpdateTenantProfileData(
            name: $validated['name'] ?? null,
            address: $validated['address'] ?? null,
            email: $validated['email'] ?? null,
            phone: $validated['phone'] ?? null,
            business_hours: $businessHours,
            presentFields: array_keys($validated),
        );
    }

    // バリデーション前にデータを準備する
    // 空文字列をnullに変換（グローバルミドルウェアに依存しない）
    protected function prepareForValidation(): void
    {
        $data = collect($this->all())
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();

        if (isset($data['business_hours']) && is_array($data['business_hours'])) {
            $data['business_hours'] = array_map(function ($hour) {
                if (! is_array($hour)) {
                    return $hour;
                }

                return array_map(
                    fn ($value) => $value === '' ? null : $value,
                    $hour
                );
            }, $data['business_hours']);
        }

        $this->merge($data);
    }
}

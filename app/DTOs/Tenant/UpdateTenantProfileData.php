<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class UpdateTenantProfileData
{
    // null と「フィールド未送信」を区別するため、リクエストに実際に含まれたフィールド名を保持する
    // （PATCH の部分更新で null 値の明示的な送信を正しく扱うために必要）
    public function __construct(
        public ?string $name = null,
        public ?string $address = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?array $business_hours = null,
        public array $presentFields = [],
    ) {}

    public function toArray(): array
    {
        $data = [];
        foreach ($this->presentFields as $field) {
            if (! property_exists($this, $field)) {
                continue;
            }

            if ($field === 'business_hours' && $this->business_hours !== null) {
                $data[$field] = array_map(fn (BusinessHourData $hour) => [
                    'weekday' => $hour->weekday,
                    'open_time' => $hour->open_time,
                    'close_time' => $hour->close_time,
                ], $this->business_hours);
            } else {
                $data[$field] = $this->{$field};
            }
        }

        return $data;
    }

    public function hasBusinessHours(): bool
    {
        return in_array('business_hours', $this->presentFields, true);
    }
}

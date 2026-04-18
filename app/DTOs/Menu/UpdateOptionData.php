<?php

declare(strict_types=1);

namespace App\DTOs\Menu;

readonly class UpdateOptionData
{
    // null と「フィールド未送信」を区別するため、リクエストに実際に含まれたフィールド名を保持する
    // （PATCH の部分更新で null 値の明示的な送信を正しく扱うために必要）
    public function __construct(
        public ?string $name = null,
        public ?int $price = null,
        public ?int $sort_order = null,
        public ?bool $is_active = null,
        public array $presentFields = [],
    ) {}

    public function toArray(): array
    {
        $data = [];
        foreach ($this->presentFields as $field) {
            if (property_exists($this, $field)) {
                $data[$field] = $this->{$field};
            }
        }

        return $data;
    }
}

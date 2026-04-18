<?php

declare(strict_types=1);

namespace App\DTOs\Menu;

readonly class UpdateMenuItemData
{
    // null と「フィールド未送信」を区別するため、リクエストに実際に含まれたフィールド名を保持する
    // （PATCH の部分更新で null 値の明示的な送信を正しく扱うために必要）
    public function __construct(
        public ?string $name = null,
        public ?int $price = null,
        public ?array $category_ids = null,
        public ?array $option_group_ids = null,
        public ?string $description = null,
        public ?bool $is_active = null,
        public ?bool $is_sold_out = null,
        public ?string $available_from = null,
        public ?string $available_until = null,
        public ?int $available_days = null,
        public ?int $sort_order = null,
        public ?int $allergens = null,
        public ?int $allergen_advisories = null,
        public ?string $allergen_note = null,
        public ?array $nutrition_info = null,
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

<?php

declare(strict_types=1);

namespace App\DTOs\Menu;

readonly class CreateMenuItemData
{
    public function __construct(
        public string $name,
        public int $price,
        public array $category_ids,
        public ?string $description = null,
        public bool $is_active = true,
        public bool $is_sold_out = false,
        public ?string $available_from = null,
        public ?string $available_until = null,
        public int $available_days = 127,
        public ?int $sort_order = null,
        public array $option_group_ids = [],
        public int $allergens = 0,
        public int $allergen_advisories = 0,
        public ?string $allergen_note = null,
        public ?array $nutrition_info = null,
    ) {}
}

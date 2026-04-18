<?php

declare(strict_types=1);

namespace App\DTOs\Menu;

readonly class CreateCategoryData
{
    public function __construct(
        public string $name,
        public ?int $sort_order = null,
        public bool $is_active = true,
    ) {}
}

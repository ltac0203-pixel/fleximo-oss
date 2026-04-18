<?php

declare(strict_types=1);

namespace App\DTOs\Menu;

readonly class CreateOptionGroupData
{
    public function __construct(
        public string $name,
        public bool $required = false,
        public int $min_select = 0,
        public int $max_select = 1,
        public ?int $sort_order = null,
        public bool $is_active = true,
    ) {}
}

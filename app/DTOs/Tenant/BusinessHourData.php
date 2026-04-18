<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class BusinessHourData
{
    public function __construct(
        public int $weekday,
        public string $open_time,
        public string $close_time,
    ) {}
}

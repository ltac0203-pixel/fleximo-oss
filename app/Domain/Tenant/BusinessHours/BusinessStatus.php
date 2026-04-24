<?php

declare(strict_types=1);

namespace App\Domain\Tenant\BusinessHours;

final readonly class BusinessStatus
{
    /**
     * @param  array<int, array{open_time: string, close_time: string}>  $todayBusinessHours
     */
    public function __construct(
        public bool $isOpen,
        public array $todayBusinessHours,
    ) {}
}

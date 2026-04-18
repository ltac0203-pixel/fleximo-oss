<?php

declare(strict_types=1);

namespace App\DTOs\Staff;

readonly class CreateStaffData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone = null,
    ) {}
}

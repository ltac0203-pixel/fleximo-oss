<?php

declare(strict_types=1);

namespace App\DTOs\Staff;

readonly class UpdateStaffData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?bool $is_active = null,
        public ?string $password = null,
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

<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class CreateTenantApplicationWithUserData
{
    public function __construct(
        public string $applicant_name,
        public string $applicant_email,
        public string $applicant_phone,
        public string $tenant_name,
        public string $business_type,
        public string $password,
        public ?string $tenant_address = null,
    ) {}
}

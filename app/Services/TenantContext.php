<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;

class TenantContext
{
    protected ?int $tenantId = null;

    protected ?Tenant $tenant = null;

    public function setTenant(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
        // tenantId 差し替え後に古い Tenant インスタンスを再利用しないようキャッシュを落とす。
        $this->tenant = null;
    }

    public function setTenantInstance(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->tenantId = $tenant->id;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getTenant(): ?Tenant
    {
        if ($this->tenant === null && $this->tenantId !== null) {
            $this->tenant = Tenant::find($this->tenantId);
        }

        return $this->tenant;
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }

    public function clear(): void
    {
        $this->tenantId = null;
        $this->tenant = null;
    }
}

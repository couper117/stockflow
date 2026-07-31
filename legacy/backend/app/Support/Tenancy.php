<?php

namespace App\Support;

// Holds the "current company" for the lifetime of a request. It is bound as a
// singleton in the container and populated by the SetTenant middleware. The
// TenantScope reads from here to filter every tenant-owned query. Keeping this
// out of the models means isolation is enforced in ONE place.
class Tenancy
{
    private ?int $companyId = null;

    public function set(?int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function id(): ?int
    {
        return $this->companyId;
    }

    public function hasCompany(): bool
    {
        return $this->companyId !== null;
    }

    public function clear(): void
    {
        $this->companyId = null;
    }
}

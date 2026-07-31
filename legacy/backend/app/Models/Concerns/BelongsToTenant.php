<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Support\Tenancy;

// Apply this trait to any tenant-owned model. It:
//   1. adds the TenantScope global scope (reads → filtered by current company), and
//   2. auto-fills company_id on create so callers never set it by hand.
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $tenancy = app(Tenancy::class);

                if ($tenancy->hasCompany()) {
                    $model->company_id = $tenancy->id();
                }
            }
        });
    }

    // Escape hatch for legitimately cross-tenant operations (e.g. resolving a
    // company by TIN at login, before any tenant is set). Use sparingly and
    // never to serve one tenant another tenant's data.
    public function scopeWithoutTenancy($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}

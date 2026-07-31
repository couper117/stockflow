<?php

namespace App\Models\Scopes;

use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

// Global scope applied to every model using the BelongsToTenant trait.
// When a current company is set, ALL queries are constrained to it — this is
// the mechanism that makes cross-company data access impossible by default.
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenancy = app(Tenancy::class);

        if ($tenancy->hasCompany()) {
            $builder->where($model->getTable().'.company_id', $tenancy->id());
        }
    }
}

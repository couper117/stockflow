<?php

namespace App\Models\Scopes;

use App\Support\ShopVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

// Global scope applied to every shop-owned model using the BelongsToShop trait.
// When the viewer is constrained to a single shop (a seller/shopkeeper), ALL
// queries are limited to that shop. Company-wide roles (Super Administrator,
// Boss, Stock Manager) see every shop. This is the mechanism behind the
// shop-level privacy rule; it composes ON TOP OF the company TenantScope.
class ShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $visibility = app(ShopVisibility::class);

        if ($visibility->isConstrained()) {
            $builder->where($model->getTable().'.shop_id', $visibility->shopId());
        }
    }
}

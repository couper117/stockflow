<?php

namespace App\Models\Concerns;

use App\Models\Scopes\ShopScope;
use App\Support\ShopVisibility;

// Apply this trait to any SHOP-owned model (shop inventory, shop-level movements
// — arriving in a later session). It:
//   1. adds the ShopScope global scope (reads → filtered to the viewer's shop
//      unless they see all shops), and
//   2. auto-fills shop_id on create from the current shop visibility.
//
// It composes with BelongsToTenant: a model may use BOTH, giving company
// isolation AND shop-level privacy. No model uses this yet — it is the ready
// concept required by the foundation (see CLAUDE.md §9.1).
trait BelongsToShop
{
    protected static function bootBelongsToShop(): void
    {
        static::addGlobalScope(new ShopScope);

        static::creating(function ($model) {
            if (empty($model->shop_id)) {
                $visibility = app(ShopVisibility::class);

                if ($visibility->shopId() !== null) {
                    $model->shop_id = $visibility->shopId();
                }
            }
        });
    }

    // Escape hatch for legitimately cross-shop operations (e.g. a Stock Manager
    // acting across shops). Use sparingly and never to leak another shop's data
    // to a user constrained to their own shop.
    public function scopeWithoutShopScope($query)
    {
        return $query->withoutGlobalScope(ShopScope::class);
    }
}

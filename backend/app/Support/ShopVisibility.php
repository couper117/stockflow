<?php

namespace App\Support;

// Holds the "current shop visibility" for the lifetime of a request, mirroring
// Tenancy. Populated from the authenticated user by the SetTenant middleware.
// The ShopScope reads from here to filter shop-owned queries.
//
// Two axes:
//   - $shopId       the shop the viewer belongs to (a seller/shopkeeper), or null
//   - $seesAllShops true for company-wide roles (Super Administrator, Boss, and
//                   the Stock Manager who runs the central Stock)
//
// This is the FOUNDATION for shop-level privacy. No business table uses it yet;
// it is applied when shop-owned inventory/movements arrive (see CLAUDE.md §11).
class ShopVisibility
{
    private ?int $shopId = null;

    private bool $seesAllShops = false;

    public function set(?int $shopId, bool $seesAllShops = false): void
    {
        $this->shopId = $shopId;
        $this->seesAllShops = $seesAllShops;
    }

    public function shopId(): ?int
    {
        return $this->shopId;
    }

    /** When true, queries are NOT constrained to a single shop. */
    public function seesAllShops(): bool
    {
        return $this->seesAllShops;
    }

    /** Whether the ShopScope should constrain to a single shop right now. */
    public function isConstrained(): bool
    {
        return ! $this->seesAllShops && $this->shopId !== null;
    }

    public function clear(): void
    {
        $this->shopId = null;
        $this->seesAllShops = false;
    }
}

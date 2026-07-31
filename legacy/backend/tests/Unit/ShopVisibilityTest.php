<?php

namespace Tests\Unit;

use App\Support\ShopVisibility;
use PHPUnit\Framework\TestCase;

// The request-scoped holder behind shop-level privacy: a shopkeeper is confined
// to one shop; company-wide roles see them all.
class ShopVisibilityTest extends TestCase
{
    public function test_defaults_to_unconstrained_with_no_shop(): void
    {
        $visibility = new ShopVisibility;

        $this->assertNull($visibility->shopId());
        $this->assertFalse($visibility->seesAllShops());
        $this->assertFalse($visibility->isConstrained());
    }

    public function test_a_shopkeeper_is_constrained_to_their_shop(): void
    {
        $visibility = new ShopVisibility;
        $visibility->set(7, false);

        $this->assertSame(7, $visibility->shopId());
        $this->assertTrue($visibility->isConstrained());
    }

    public function test_a_company_wide_role_is_never_constrained(): void
    {
        $visibility = new ShopVisibility;
        // Even with a shop id, seeing all shops wins.
        $visibility->set(7, true);

        $this->assertTrue($visibility->seesAllShops());
        $this->assertFalse($visibility->isConstrained());
    }

    public function test_clear_resets_the_context(): void
    {
        $visibility = new ShopVisibility;
        $visibility->set(7, false);
        $visibility->clear();

        $this->assertNull($visibility->shopId());
        $this->assertFalse($visibility->isConstrained());
    }
}

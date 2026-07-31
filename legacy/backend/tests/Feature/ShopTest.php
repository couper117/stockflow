<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Shops are tenant-owned: one company must never see another company's shops
// (see CLAUDE.md §9). Also covers the user ↔ assigned-shop relationship.
class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_shops_are_scoped_to_the_current_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Shop::factory()->count(2)->forCompany($companyA)->create();
        Shop::factory()->forCompany($companyB)->create();

        app(Tenancy::class)->set($companyA->id);
        $this->assertSame(2, Shop::count());

        app(Tenancy::class)->set($companyB->id);
        $this->assertSame(1, Shop::count());
    }

    public function test_company_a_cannot_read_a_company_b_shop(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $shopB = Shop::factory()->forCompany($companyB)->create();

        app(Tenancy::class)->set($companyA->id);

        $this->assertNull(Shop::find($shopB->id));
    }

    public function test_a_new_shop_is_auto_assigned_to_the_current_company(): void
    {
        $company = Company::factory()->create();
        app(Tenancy::class)->set($company->id);

        $shop = Shop::create(['name' => 'Auto Shop']);

        $this->assertSame($company->id, $shop->company_id);
    }

    public function test_a_seller_belongs_to_its_assigned_shop(): void
    {
        $company = Company::factory()->create();
        $role = Role::factory()->named(Role::SHOPKEEPER)->create();
        $shop = Shop::factory()->forCompany($company)->create();

        $seller = User::factory()
            ->forCompany($company)
            ->withRole($role)
            ->create(['assigned_shop_id' => $shop->id]);

        $this->assertTrue($seller->shop->is($shop));
        $this->assertTrue($shop->users->contains($seller));
        $this->assertFalse($seller->seesAllShops());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The demo seed must produce a realistic company: the four roles, plus a Super
// Administrator, a Stock Manager, and one shop with a Seller assigned to it.
// It must also be idempotent (safe to re-run).
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_four_roles_and_a_staffed_demo_company(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', 4);

        $company = Company::where('tin_number', '100200300')->firstOrFail();
        $shop = Shop::where('company_id', $company->id)->firstOrFail();

        $superAdmin = User::where('email', 'admin@demo.test')->firstOrFail();
        $stockManager = User::where('email', 'stock@demo.test')->firstOrFail();
        $seller = User::where('email', 'seller@demo.test')->firstOrFail();

        $this->assertSame(Role::SUPER_ADMIN, $superAdmin->role->name);
        $this->assertNull($superAdmin->assigned_shop_id);

        $this->assertSame(Role::STOCK_MANAGER, $stockManager->role->name);

        $this->assertSame(Role::SHOPKEEPER, $seller->role->name);
        $this->assertSame($shop->id, $seller->assigned_shop_id);
        $this->assertFalse($seller->seesAllShops());
    }

    public function test_the_seed_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $company = Company::where('tin_number', '100200300')->firstOrFail();

        $this->assertDatabaseCount('roles', 4);
        $this->assertSame(1, Company::where('tin_number', '100200300')->count());
        $this->assertSame(1, Shop::where('company_id', $company->id)->count());
        $this->assertSame(3, User::where('company_id', $company->id)->count());
    }
}

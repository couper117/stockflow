<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Concerns\BelongsToShop;
use App\Models\Concerns\BelongsToTenant;
use App\Support\ShopVisibility;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Proves the ShopScope CONCEPT actually enforces shop-level privacy, using a
// throwaway shop-owned model. No production table uses BelongsToShop yet (that
// arrives with shop inventory in a later session), so this guards the mechanism
// the future feature will rely on. Shop privacy composes on top of tenant
// isolation — see CLAUDE.md §9.1.
class ShopScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A minimal shop-owned table for the throwaway model below.
        Schema::dropIfExists('scoped_items');
        Schema::create('scoped_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function test_a_constrained_viewer_sees_only_their_own_shop(): void
    {
        $company = Company::factory()->create();
        app(Tenancy::class)->set($company->id);

        $visibility = app(ShopVisibility::class);

        // Seed items in two shops while unconstrained.
        $visibility->set(10, true);
        ScopedItemStub::create(['shop_id' => 10, 'name' => 'shop-10 item']);
        ScopedItemStub::create(['shop_id' => 20, 'name' => 'shop-20 item']);

        // A shopkeeper confined to shop 10 sees only shop 10.
        $visibility->set(10, false);
        $this->assertSame(1, ScopedItemStub::count());
        $this->assertSame('shop-10 item', ScopedItemStub::first()->name);

        // A company-wide role (Boss/Stock Manager/Super Admin) sees both.
        $visibility->set(10, true);
        $this->assertSame(2, ScopedItemStub::count());
    }

    public function test_new_shop_owned_records_get_the_current_shop_id(): void
    {
        $company = Company::factory()->create();
        app(Tenancy::class)->set($company->id);
        app(ShopVisibility::class)->set(15, false);

        $item = ScopedItemStub::create(['name' => 'auto-shop item']);

        $this->assertSame(15, $item->shop_id);
        $this->assertSame($company->id, $item->company_id);
    }
}

// Throwaway model exercising BOTH scopes together (company + shop).
class ScopedItemStub extends Model
{
    use BelongsToShop, BelongsToTenant;

    protected $table = 'scoped_items';

    protected $guarded = [];
}

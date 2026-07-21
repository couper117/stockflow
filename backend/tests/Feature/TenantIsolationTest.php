<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The single most important guarantee of this system: one company can NEVER see
// or touch another company's data (see CLAUDE.md §9). If this test ever fails,
// treat it as a critical security bug.
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;

    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->named(Role::SUPER_ADMIN)->create();

        $this->companyA = Company::factory()->create(['tin_number' => '111111111']);
        $this->companyB = Company::factory()->create(['tin_number' => '222222222']);

        User::factory()->count(2)->forCompany($this->companyA)->withRole($role)->create();
        User::factory()->count(3)->forCompany($this->companyB)->withRole($role)->create();
    }

    public function test_queries_are_scoped_to_the_current_company(): void
    {
        app(Tenancy::class)->set($this->companyA->id);
        $this->assertSame(2, User::count());
        $this->assertEquals([$this->companyA->id], User::query()->pluck('company_id')->unique()->values()->all());

        app(Tenancy::class)->set($this->companyB->id);
        $this->assertSame(3, User::count());
        $this->assertEquals([$this->companyB->id], User::query()->pluck('company_id')->unique()->values()->all());
    }

    public function test_company_a_cannot_read_a_company_b_record(): void
    {
        $companyBUser = $this->companyB->users()->first();

        app(Tenancy::class)->set($this->companyA->id);

        // Even when we ask for a known ID from company B, the scope hides it.
        $this->assertNull(User::find($companyBUser->id));
    }

    public function test_new_records_are_auto_assigned_to_the_current_company(): void
    {
        app(Tenancy::class)->set($this->companyA->id);

        $role = Role::where('name', Role::SUPER_ADMIN)->first();
        $user = User::create([
            'role_id' => $role->id,
            'name' => 'Scoped User',
            'email' => 'scoped@a.test',
            'password' => 'password',
        ]);

        $this->assertSame($this->companyA->id, $user->company_id);
    }

    public function test_api_me_only_ever_exposes_the_callers_own_company(): void
    {
        $userA = $this->companyA->users()->first();

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.company.tin_number', '111111111');
    }
}

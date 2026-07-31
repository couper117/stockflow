<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// One demo company for local development / manual QA: a Super Administrator, a
// Stock Manager, and one shop with a Seller (shopkeeper) assigned to it.
// Idempotent (safe to re-run). Runs with no tenant context, so company_id is
// always set explicitly.
class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['tin_number' => '100200300'],
            ['name' => 'Demo Trading Ltd', 'is_active' => true],
        );

        $roles = Role::whereIn('name', [
            Role::SUPER_ADMIN,
            Role::STOCK_MANAGER,
            Role::SHOPKEEPER,
        ])->pluck('id', 'name');

        $shop = Shop::updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Kigali Main Shop'],
            ['is_active' => true],
        );

        // All demo users share the password "password".
        $this->user($company->id, $roles[Role::SUPER_ADMIN], 'admin@demo.test', 'Demo Super Admin');
        $this->user($company->id, $roles[Role::STOCK_MANAGER], 'stock@demo.test', 'Demo Stock Manager');
        $this->user($company->id, $roles[Role::SHOPKEEPER], 'seller@demo.test', 'Demo Seller', $shop->id);
    }

    private function user(int $companyId, int $roleId, string $email, string $name, ?int $shopId = null): void
    {
        User::updateOrCreate(
            ['company_id' => $companyId, 'email' => $email],
            [
                'role_id' => $roleId,
                'name' => $name,
                'password' => Hash::make('password'),
                'locale' => 'en',
                'assigned_shop_id' => $shopId,
            ],
        );
    }
}

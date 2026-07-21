<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// One demo company with a Super Administrator, for local development / manual QA.
class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::updateOrCreate(
            ['tin_number' => '100200300'],
            ['name' => 'Demo Trading Ltd', 'is_active' => true],
        );

        $superAdminRole = Role::where('name', Role::SUPER_ADMIN)->firstOrFail();

        User::updateOrCreate(
            ['company_id' => $company->id, 'email' => 'admin@demo.test'],
            [
                'role_id' => $superAdminRole->id,
                'name' => 'Demo Super Admin',
                'password' => Hash::make('password'),
                'locale' => 'en',
            ],
        );
    }
}

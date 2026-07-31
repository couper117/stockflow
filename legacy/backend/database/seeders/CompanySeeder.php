<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

// A few example companies (tenants) for local development and manual QA of the
// company registration/listing endpoints. Idempotent (safe to re-run).
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['tin_number' => '123456789', 'name' => 'Kigali Textiles Ltd', 'is_active' => true],
            ['tin_number' => '987654321', 'name' => 'Rwanda Fabrics Co', 'is_active' => true],
            ['tin_number' => '456789123', 'name' => 'Nyamirambo Curtains Ltd', 'is_active' => false],
        ];

        foreach ($companies as $company) {
            Company::updateOrCreate(
                ['tin_number' => $company['tin_number']],
                ['name' => $company['name'], 'is_active' => $company['is_active']],
            );
        }
    }
}

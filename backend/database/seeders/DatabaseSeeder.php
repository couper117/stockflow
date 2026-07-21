<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,        // global reference data first
            DemoCompanySeeder::class, // demo tenant + super admin
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

// Seeds the four fixed system roles. Idempotent (safe to re-run).
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Role::ALL as $name => $labelKey) {
            Role::updateOrCreate(
                ['name' => $name],
                ['label_key' => $labelKey],
            );
        }
    }
}

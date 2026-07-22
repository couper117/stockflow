<?php

namespace Tests\Feature;

use App\Models\Company;
use Database\Seeders\CompanySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_example_companies(): void
    {
        $this->seed(CompanySeeder::class);

        $this->assertDatabaseHas('companies', ['tin_number' => '123456789', 'name' => 'Kigali Textiles Ltd', 'is_active' => true]);
        $this->assertDatabaseHas('companies', ['tin_number' => '987654321', 'name' => 'Rwanda Fabrics Co', 'is_active' => true]);
        $this->assertDatabaseHas('companies', ['tin_number' => '456789123', 'is_active' => false]);
    }

    public function test_the_seed_is_idempotent(): void
    {
        $this->seed(CompanySeeder::class);
        $this->seed(CompanySeeder::class);

        $this->assertSame(1, Company::where('tin_number', '123456789')->count());
        $this->assertDatabaseCount('companies', 3);
    }
}

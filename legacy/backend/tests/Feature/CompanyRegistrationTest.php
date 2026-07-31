<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// POST /api/companies — tenant registration. Covers the happy path, TIN
// uniqueness, Rwanda TIN format, required fields, mass-assignment protection,
// and bilingual (en/rw) error messages.
class CompanyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_can_be_registered(): void
    {
        $response = $this->postJson('/api/companies', [
            'name' => 'Kigali Textiles Ltd',
            'tin_number' => '123456789',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Kigali Textiles Ltd')
            ->assertJsonPath('data.tin_number', '123456789')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('message', 'Company registered successfully.');

        $this->assertDatabaseHas('companies', [
            'tin_number' => '123456789',
            'name' => 'Kigali Textiles Ltd',
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_a_duplicate_tin(): void
    {
        Company::factory()->create(['tin_number' => '123456789']);

        $this->postJson('/api/companies', [
            'name' => 'Another Company',
            'tin_number' => '123456789',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('tin_number');

        $this->assertSame(1, Company::where('tin_number', '123456789')->count());
    }

    public function test_a_soft_deleted_companys_tin_cannot_be_reused(): void
    {
        $company = Company::factory()->create(['tin_number' => '123456789']);
        $company->delete();

        $this->postJson('/api/companies', [
            'name' => 'Reuse Attempt',
            'tin_number' => '123456789',
        ])->assertStatus(422)->assertJsonValidationErrors('tin_number');
    }

    /** @return array<string, array{string}> */
    public static function invalidTins(): array
    {
        return [
            'letters' => ['12345678a'],
            'too short' => ['12345678'],
            'too long' => ['1234567890'],
            'symbols' => ['123-45678'],
            'spaces' => ['123 45678'],
            'empty' => [''],
        ];
    }

    #[DataProvider('invalidTins')]
    public function test_it_rejects_invalid_tins(string $tin): void
    {
        $this->postJson('/api/companies', [
            'name' => 'Bad TIN Company',
            'tin_number' => $tin,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('tin_number');

        $this->assertDatabaseCount('companies', 0);
    }

    public function test_it_requires_a_name(): void
    {
        $this->postJson('/api/companies', ['tin_number' => '123456789'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_is_active_cannot_be_set_from_the_request(): void
    {
        // A client trying to smuggle is_active=false must be ignored.
        $this->postJson('/api/companies', [
            'name' => 'Sneaky Company',
            'tin_number' => '123456789',
            'is_active' => false,
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('companies', ['tin_number' => '123456789', 'is_active' => true]);
    }

    public function test_validation_errors_are_localized_in_kinyarwanda(): void
    {
        Company::factory()->create(['tin_number' => '123456789']);

        $response = $this->postJson('/api/companies', [
            'name' => 'Duplicate Company',
            'tin_number' => '123456789',
        ], ['Accept-Language' => 'rw']);

        $response->assertStatus(422);
        $this->assertSame('TIN isanzwe ikoreshwa.', $response->json('errors.tin_number.0'));
    }
}

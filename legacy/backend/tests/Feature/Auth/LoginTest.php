<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $companyAttrs = [], array $userAttrs = []): User
    {
        $company = Company::factory()->create(array_merge(['tin_number' => '100200300'], $companyAttrs));
        $role = Role::factory()->named(Role::SUPER_ADMIN)->create();

        return User::factory()
            ->forCompany($company)
            ->withRole($role)
            ->create(array_merge([
                'email' => 'admin@demo.test',
                'password' => Hash::make('password'),
            ], $userAttrs));
    }

    public function test_user_can_log_in_with_valid_tin_email_and_password(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/auth/login', [
            'tin_number' => '100200300',
            'email' => 'admin@demo.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'company' => ['tin_number']]]]);

        $this->assertSame('100200300', $response->json('data.user.company.tin_number'));
    }

    public function test_login_fails_with_wrong_password_using_a_generic_message(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/auth/login', [
            'tin_number' => '100200300',
            'email' => 'admin@demo.test',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Invalid TIN, email, or password.']);
        $response->assertJsonMissingPath('data.token');
    }

    public function test_login_fails_for_unknown_tin_without_revealing_which_field(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/auth/login', [
            'tin_number' => '999999999',
            'email' => 'admin@demo.test',
            'password' => 'password',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid TIN, email, or password.']);
    }

    public function test_login_fails_for_inactive_company(): void
    {
        $this->makeUser(['is_active' => false]);

        $response = $this->postJson('/api/auth/login', [
            'tin_number' => '100200300',
            'email' => 'admin@demo.test',
            'password' => 'password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_requires_a_valid_9_digit_tin(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'tin_number' => '12345',
            'email' => 'admin@demo.test',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tin_number']);
    }

    public function test_login_returns_localized_error_in_kinyarwanda(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/auth/login', [
            'tin_number' => '100200300',
            'email' => 'admin@demo.test',
            'password' => 'wrong-password',
        ], ['Accept-Language' => 'rw']);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'TIN, imeyili, cyangwa ijambobanga si byo.']);
    }
}

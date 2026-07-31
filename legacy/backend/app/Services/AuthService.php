<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// All authentication business logic lives here; the controller stays thin.
class AuthService
{
    /**
     * Authenticate a user within their company (resolved by TIN) and issue a
     * personal access token.
     *
     * @param  array{tin_number: string, email: string, password: string}  $credentials
     * @return array{user: User, token: string}
     *
     * @throws InvalidCredentialsException on ANY failure (never reveals which field).
     */
    public function login(array $credentials): array
    {
        $company = Company::where('tin_number', $credentials['tin_number'])
            ->where('is_active', true)
            ->first();

        if (! $company) {
            throw new InvalidCredentialsException;
        }

        // No tenant is set yet at login, so this query is not auto-scoped; we
        // constrain to the resolved company explicitly.
        $user = User::where('company_id', $company->id)
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new InvalidCredentialsException;
        }

        $token = $user->createToken('api')->plainTextToken;

        return ['user' => $user->load(['company', 'role']), 'token' => $token];
    }

    public function logout(User $user): void
    {
        // Revoke only the token used for this request.
        $user->currentAccessToken()->delete();
    }
}

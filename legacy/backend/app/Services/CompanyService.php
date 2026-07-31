<?php

namespace App\Services;

use App\Models\Company;

// Company (tenant) business logic. Kept in a service so the controller stays
// thin and so tenant creation has ONE home — later it will also provision the
// first Super Administrator, a default shop and the central stock in a single
// transaction.
class CompanyService
{
    /**
     * Register a new company (tenant). is_active is forced on here so it can
     * never be set by the client.
     *
     * @param  array{name: string, tin_number: string}  $data
     */
    public function register(array $data): Company
    {
        return Company::create([
            'name' => $data['name'],
            'tin_number' => $data['tin_number'],
            'is_active' => true,
        ]);
    }
}

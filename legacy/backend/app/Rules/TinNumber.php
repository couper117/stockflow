<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

// Rwanda TIN (Tax Identification Number) = exactly 9 digits.
// Reusable wherever a TIN is accepted (login, company creation, ...).
class TinNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail('validation.tin_number')->translate();

            return;
        }

        if (preg_match('/^\d{9}$/', (string) $value) !== 1) {
            $fail('validation.tin_number')->translate();
        }
    }
}

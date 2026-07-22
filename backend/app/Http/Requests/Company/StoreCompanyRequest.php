<?php

namespace App\Http\Requests\Company;

use App\Rules\TinNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validates a company (tenant) registration. Only name + tin_number are
// accepted from the client; is_active is set by the service, never the request
// (mass-assignment protection). Attribute names and rule messages are localized
// (lang/{en,rw}/validation.php).
class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public onboarding endpoint (see routes/api.php); no policy gate yet.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:255'],
            // TIN is globally unique across ALL companies (incl. soft-deleted:
            // the unique rule queries the table directly, so a trashed tenant's
            // TIN can never be re-registered).
            'tin_number' => ['bail', 'required', 'string', new TinNumber, Rule::unique('companies', 'tin_number')],
        ];
    }
}

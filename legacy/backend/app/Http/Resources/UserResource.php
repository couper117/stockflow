<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
            'assigned_shop_id' => $this->assigned_shop_id,
            'assigned_stock_id' => $this->assigned_stock_id,
            'role' => $this->whenLoaded('role', fn () => [
                'name' => $this->role->name,
                'label_key' => $this->role->label_key,
            ]),
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'tin_number' => $this->company->tin_number,
            ]),
        ];
    }
}

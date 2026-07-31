<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'role_id',
        'name',
        'email',
        'password',
        'locale',
        'assigned_shop_id',
        'assigned_stock_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** The shop this user works in (sellers/shopkeepers); null otherwise. */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'assigned_shop_id');
    }

    public function hasRole(string $name): bool
    {
        return $this->role?->name === $name;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    // Company-wide roles see every shop; a shopkeeper is confined to their own.
    // This drives the ShopScope (shop-level privacy — see CLAUDE.md §9.1).
    public function seesAllShops(): bool
    {
        return in_array(
            $this->role?->name,
            [Role::SUPER_ADMIN, Role::BOSS, Role::STOCK_MANAGER],
            true,
        );
    }
}

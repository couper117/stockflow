<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Global (non-tenant) reference data. The four fixed roles of the system.
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const SUPER_ADMIN = 'super_admin';

    public const STOCK_MANAGER = 'stock_manager';

    public const SHOPKEEPER = 'shopkeeper';

    public const BOSS = 'boss';

    /** All role machine-names paired with their translation keys. */
    public const ALL = [
        self::SUPER_ADMIN => 'roles.super_admin',
        self::STOCK_MANAGER => 'roles.stock_manager',
        self::SHOPKEEPER => 'roles.shopkeeper',
        self::BOSS => 'roles.boss',
    ];

    protected $fillable = [
        'name',
        'label_key',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

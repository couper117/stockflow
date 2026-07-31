<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// A tenant-owned selling location. Scoped to its company by BelongsToTenant.
class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** The sellers/shopkeepers who work in this shop. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'assigned_shop_id');
    }
}

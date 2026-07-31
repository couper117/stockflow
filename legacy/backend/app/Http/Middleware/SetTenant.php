<?php

namespace App\Http\Middleware;

use App\Support\ShopVisibility;
use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Binds the authenticated user's request context so every scoped query is
// automatically constrained. Must run AFTER auth:sanctum. It sets:
//   - the current company (TenantScope → company isolation), and
//   - the current shop visibility (ShopScope → shop-level privacy).
class SetTenant
{
    public function __construct(
        private Tenancy $tenancy,
        private ShopVisibility $shopVisibility,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->company_id) {
            $this->tenancy->set($user->company_id);
            $this->shopVisibility->set($user->assigned_shop_id, $user->seesAllShops());
        }

        return $next($request);
    }
}

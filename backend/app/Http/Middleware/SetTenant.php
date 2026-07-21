<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Binds the authenticated user's company as the current tenant, so every
// tenant-scoped query is automatically constrained. Must run AFTER auth:sanctum.
class SetTenant
{
    public function __construct(private Tenancy $tenancy) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->company_id) {
            $this->tenancy->set($user->company_id);
        }

        return $next($request);
    }
}

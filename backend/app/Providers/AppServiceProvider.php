<?php

namespace App\Providers;

use App\Support\Tenancy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One tenancy context per request, shared by the SetTenant middleware
        // (writer) and the TenantScope (reader).
        $this->app->singleton(Tenancy::class);
    }

    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Picks the response language from the Accept-Language header, restricted to the
// locales the app actually supports (config: app.supported_locales). Falls back
// to the app default. This drives localized error/validation messages.
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['en']);
        $requested = $request->getPreferredLanguage($supported);

        if ($requested && in_array($requested, $supported, true)) {
            app()->setLocale($requested);
        }

        return $next($request);
    }
}

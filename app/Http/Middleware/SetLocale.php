<?php

namespace App\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the signed-in user's preferred locale (§46).
 *
 * The app ships in French; English is already wired so a second lang/ folder
 * is the only work needed to enable it. Falls back to the configured default
 * for guests and for any locale we do not actually ship.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? config('app.locale');

        if (! in_array($locale, config('app.supported_locales', ['fr']), true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        CarbonImmutable::setLocale($locale);
        setlocale(LC_TIME, $locale);

        return $next($request);
    }
}

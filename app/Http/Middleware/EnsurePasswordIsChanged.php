<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds an account that signed in with a temporary password on the
 * change-password screen until it picks a real one (§4).
 *
 * Applied to the whole authenticated area rather than sprinkled over
 * individual routes, so a bookmark or a stray link cannot walk around it.
 */
class EnsurePasswordIsChanged
{
    /**
     * Routes that stay reachable while the change is outstanding.
     *
     * The change screen itself, obviously — and logout, so somebody who
     * decides not to continue is not trapped in the application.
     */
    private const ALLOWED = [
        'password.change',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs(self::ALLOWED)) {
            return $next($request);
        }

        // Livewire's own endpoint has to stay open, or the change form could
        // not submit: it posts to /livewire/update, not to the page's route.
        if ($request->is('livewire/*')) {
            return $next($request);
        }

        return redirect()->route('password.change');
    }
}

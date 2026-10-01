<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to users holding one of the given roles.
 *
 * Applied as `role:admin` or `role:staff,admin`. Unauthenticated visitors are
 * sent to the login screen; authenticated users without the role get a 403
 * rather than a redirect, so a buyer never sees an admin URL silently work.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }

            return redirect()->guest(route('login'));
        }

        abort_unless(in_array($user->role, $roles, true), 403);

        return $next($request);
    }
}

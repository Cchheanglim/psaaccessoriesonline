<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only signed-in Staff or Admin accounts may pass. Guests go to the login page
 * (and come back afterwards); signed-in buyers are sent to the storefront.
 */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                abort(401, 'Please log in first.');
            }
            $page = ltrim($request->getRequestUri(), '/');

            return redirect('/login.html?next='.urlencode($page));
        }

        if (! $user->isStaff() || $user->status === 'Suspended') {
            abort_if($request->expectsJson(), 403, 'Your role does not allow this.');

            return redirect('/home.html');
        }

        return $next($request);
    }
}

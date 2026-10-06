<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A browser keeps one sign-in per site, so signing in as another account in one tab silently
 * changes it for every tab. The pages send the account they were opened as (X-PSA-User); a change
 * coming from a different account is refused, so a customer's page can't send messages, slips or
 * orders as the admin (or the other way round). Signing in, signing out and reading are not checked.
 */
class EnsureSameAccount
{
    private const SKIP = ['api/auth/login', 'api/auth/register', 'api/auth/logout'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader('X-PSA-User') || $request->isMethodSafe() || in_array($request->path(), self::SKIP, true)) {
            return $next($request);
        }

        $expected = (string) $request->header('X-PSA-User');
        $actual = (string) ($request->user()?->id ?? '');

        if ($expected !== $actual) {
            return response()->json([
                'message' => 'You signed in as a different account in another tab. Reload this page to continue as that account.',
                'accountChanged' => true,
            ], 409);
        }

        return $next($request);
    }
}

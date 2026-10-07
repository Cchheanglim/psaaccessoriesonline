<?php

namespace App\Http\Middleware;

use App\Models\OrderMessage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs the chat clean-up (OrderMessage::cleanUp) at most once an hour, on whichever request comes first.
 * The host only runs the web server (no scheduler), so the website's own traffic triggers it.
 */
class CleanUpOldMessages
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Cache::add('chat_clean_up', true, now()->addHour())) {
            try {
                OrderMessage::cleanUp();
            } catch (\Throwable $e) {
                Cache::forget('chat_clean_up'); // try again on the next request
                report($e);
            }
        }

        return $next($request);
    }
}

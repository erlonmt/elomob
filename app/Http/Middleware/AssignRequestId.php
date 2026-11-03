<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Give each request an id, which every log entry written while handling it carries (Laravel's Context) and the
 * response returns in X-Request-Id. The id nginx creates at the entrance is kept, so its access log, the Next.js
 * proxy and the API logs name a request alike; any other value than letters, digits and dashes is replaced.
 */
class AssignRequestId
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $given = (string) $request->headers->get('X-Request-Id', '');
        $id = preg_match('/^[A-Za-z0-9-]{8,64}$/', $given) === 1 ? $given : (string) Str::uuid();
        Context::add('request_id', $id);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $id);

        return $response;
    }
}

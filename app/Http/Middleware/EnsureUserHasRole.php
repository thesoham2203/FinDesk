<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use function in_array;

final class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  list<string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // TODO: Get the authenticated user
        $user = $request->user();

        // TODO: Check if user->role matches one of the provided $roles
        if ($user && in_array($user->role, $roles, true)) {
            // TODO: If match found → return $next($request)
            return $next($request);
        }
        // TODO: If no match → abort(403, 'Unauthorized role for this section')
        abort(404);
    }
}

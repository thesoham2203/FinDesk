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
        $user = $request->user();

        if ($user && in_array($user->role->value, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Unauthorized role for this section');
    }
}

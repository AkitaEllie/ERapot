<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a route unless the signed-in user's role grants the named capability.
 */
class EnsureHakAkses
{
    public function handle(Request $request, Closure $next, string $hakAkses): Response
    {
        $role = $request->user()?->role;

        abort_unless($role?->punyaHakAkses($hakAkses), 403);

        return $next($request);
    }
}

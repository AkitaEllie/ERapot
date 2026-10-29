<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a route unless the signed-in user's role grants the named capability.
 *
 * Several capabilities may be listed ("hak:ekspor,persetujuan"); holding any one
 * of them is enough.
 */
class EnsureHakAkses
{
    public function handle(Request $request, Closure $next, string ...$hakAkses): Response
    {
        $role = $request->user()?->role;

        abort_unless(
            $role !== null && collect($hakAkses)->contains(fn (string $hak): bool => $role->punyaHakAkses($hak)),
            403,
        );

        return $next($request);
    }
}

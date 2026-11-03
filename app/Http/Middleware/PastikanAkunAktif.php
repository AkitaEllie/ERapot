<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends the session of an account switched off on Data Guru & Pengguna.
 *
 * The login screen refuses inactive accounts, but a session opened before the
 * switch would otherwise keep working until it expired.
 */
class PastikanAkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->Is_Active) {
            $user->logout();

            return redirect()->route('login')->with('status', 'Akun Anda telah dinonaktifkan. Hubungi Tata Usaha.');
        }

        return $next($request);
    }
}

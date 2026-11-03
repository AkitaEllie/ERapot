<?php

namespace App\Http\Controllers;

use App\Models\Dokumentasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a student's activity photo to the people who may read their report.
 * Photos live on a private disk, so this is the only way to reach one.
 */
class FotoDokumentasiController extends Controller
{
    public function __invoke(Request $request, Dokumentasi $dokumentasi): StreamedResponse
    {
        abort_unless($dokumentasi->narasi->rapor->bolehDilihatOleh($request->user()), 403);
        abort_unless(filled($dokumentasi->Foto) && Storage::disk(Dokumentasi::DISK)->exists($dokumentasi->Foto), 404);

        // Never cached: on a computer the staff share, the browser would hand a
        // saved copy to the next person to sign in, without asking this route.
        return Storage::disk(Dokumentasi::DISK)->response($dokumentasi->Foto, headers: [
            'Cache-Control' => 'no-store',
        ]);
    }
}

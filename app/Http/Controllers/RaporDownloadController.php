<?php

namespace App\Http\Controllers;

use App\Enums\RaporStatus;
use App\Models\Rapor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams an exported report card as a download.
 *
 * The class diagram models the export as Rapor::eksporPDF(), which
 * produces the file, but says nothing about how the browser receives it. That
 * transport lives here.
 */
class RaporDownloadController extends Controller
{
    public function __invoke(Request $request, Rapor $rapor): StreamedResponse|Response
    {
        // Deliberate: any teacher assigned in the class may download the whole
        // book, matching the classes Daftar Rapor & Export lists for them, even
        // though the on-screen preview shows a subject teacher only their own
        // subject. Narrow this to milikWaliKelas() if that ever changes.
        abort_unless($rapor->bolehDibukaOleh($request->user()), 403);
        abort_unless($rapor->Status === RaporStatus::Disetujui, 403, 'Rapor belum disetujui.');

        // Export on demand when the stored copy is gone or out of date, so a shared
        // link always yields this child's current report.
        $berkas = $rapor->berkasPdfMasihBerlaku() ? $rapor->berkasPdf() : $rapor->eksporPDF();

        $nama = 'rapor-'.$rapor->siswa->NISN.'-'.$rapor->Semester->value.'.pdf';

        return Storage::disk(Rapor::DISK_PDF)->download($berkas, $nama);
    }
}

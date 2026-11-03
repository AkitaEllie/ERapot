{{-- The report document itself, shared by the preview screen and the PDF export so
     the two cannot drift apart.

     The wrapper carries the report's font stack; without it the CJK face declared
     in gaya.blade.php is never applied and Mandarin exports as blank glyphs.

     Props:
     - $rapor: the report being rendered.
     - $identitas: whether to include the cover block and the closing pages. True
       for the homeroom teacher's full book; false for a subject teacher, who sees
       only their own subject pages.
     - $intrakurikuler / $kokurikuler / $tabel: the subject groups from Rapor::seksi(). --}}
@props([
    'rapor',
    'identitas' => true,
    'intrakurikuler' => collect(),
    'kokurikuler' => collect(),
    'tabel' => collect(),
    'untukPdf' => false,
])

@if ($untukPdf)
    {{-- The export cannot see the app's compiled Tailwind bundle, so the document's
         design is restated in plain CSS. --}}
    <x-rapor.gaya-pdf />
@else
    <x-rapor.gaya />
@endif

<div class="rapor-dokumen text-[13px] leading-relaxed text-[#111]">
    @if ($identitas)
        @include('components.rapor.identitas', ['rapor' => $rapor])

        {{-- Each letter heading shares a block with its first subject, so it is never
             stranded at the foot of a page. --}}
        @if ($intrakurikuler->isNotEmpty())
            <div class="tetap-bersama">
                <h2 class="judul-bagian mt-6 mb-3 font-bold">A. INTRAKURIKULER</h2>
                <x-rapor.seksi-prosa :narasi="$intrakurikuler->first()['narasi']" :mapel="$intrakurikuler->first()['mapel']" :untuk-pdf="$untukPdf" />
            </div>
            @foreach ($intrakurikuler->skip(1) as $item)
                <x-rapor.seksi-prosa :narasi="$item['narasi']" :mapel="$item['mapel']" :untuk-pdf="$untukPdf" />
            @endforeach
        @endif

        @if ($kokurikuler->isNotEmpty())
            @foreach ($kokurikuler as $item)
                <div @class(['tetap-bersama' => $loop->first])>
                    @if ($loop->first)
                        <h2 class="judul-bagian mt-8 mb-3 font-bold">B. KOKURIKULER</h2>
                    @endif
                    <x-rapor.seksi-prosa
                        :narasi="$item['narasi']"
                        :mapel="$item['mapel']"
                        :judul="'Deskripsi Pengembangan Proyek ('.$item['mapel']->Nama_Mapel.')'"
                        :untuk-pdf="$untukPdf"
                    />
                </div>
            @endforeach
        @endif

        <x-rapor.penutup :rapor="$rapor" />
    @endif

    {{-- Table-template subjects, one page each. --}}
    @foreach ($tabel as $item)
        <div class="{{ $loop->first && ! $identitas ? '' : 'halaman-baru mt-12 border-t-2 border-dashed border-hairline pt-10' }}">
            <x-rapor.seksi-tabel
                :narasi="$item['narasi']"
                :mapel="$item['mapel']"
                :rapor="$rapor"
                :pengampu="$item['pengampu']"
                :untuk-pdf="$untukPdf"
            />
        </div>
    @endforeach
</div>

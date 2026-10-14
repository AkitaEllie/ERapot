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

        @if ($intrakurikuler->isNotEmpty())
            <h2 class="mt-6 mb-3 font-bold">A. INTRAKURIKULER</h2>
            @foreach ($intrakurikuler as $item)
                <x-rapor.seksi-prosa :narasi="$item['narasi']" :mapel="$item['mapel']" :untuk-pdf="$untukPdf" />
            @endforeach
        @endif

        @if ($kokurikuler->isNotEmpty())
            <h2 class="mt-8 mb-3 font-bold">B. KOKURIKULER</h2>
            @foreach ($kokurikuler as $item)
                <x-rapor.seksi-prosa
                    :narasi="$item['narasi']"
                    :mapel="$item['mapel']"
                    :judul="'Deskripsi Pengembangan Proyek ('.$item['mapel']->Nama_Mapel.')'"
                    :untuk-pdf="$untukPdf"
                />
            @endforeach
        @endif

        <x-rapor.penutup :rapor="$rapor" />
    @endif

    {{-- Table-template subjects, one page each. --}}
    @foreach ($tabel as $item)
        <div class="{{ $loop->first && ! $identitas ? '' : 'mt-12 border-t-2 border-dashed border-hairline pt-10' }}">
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

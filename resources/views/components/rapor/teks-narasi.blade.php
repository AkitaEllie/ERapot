{{-- A stored narrative, printed the same way on every template.

     Blank lines separate blocks. Inside a block, a line starting with a number or
     a dash is a list item. The plain lines after a numbered item are its description,
     as on the Kokurikuler page of docs/contoh-rapor.pdf; a dash item takes none, so
     a sentence after a bullet list is ordinary text. Marked up line by line because
     dompdf cannot hang a description under its number. --}}
@props(['teks' => null, 'kosong' => 'Belum ada narasi.'])

@php
    $blok = array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/u', trim((string) $teks)))));
@endphp

@forelse ($blok as $isi)
    @php $bawahNomor = false; @endphp
    @foreach (preg_split('/\R/u', $isi) as $baris)
        @php
            $baris = trim($baris);
            $bernomor = preg_match('/^\d+[.)]\s+/u', $baris) === 1;
            $butir = $bernomor || preg_match('/^[-•]\s+/u', $baris) === 1;
            $rincian = ! $butir && $bawahNomor;
            $bawahNomor = $bernomor || $rincian;
        @endphp
        <p class="{{ $butir ? 'narasi-butir' : ($rincian ? 'narasi-rincian' : 'mb-2') }}">{{ $baris }}</p>
    @endforeach
@empty
    <p class="text-[#888]">{{ $kosong }}</p>
@endforelse

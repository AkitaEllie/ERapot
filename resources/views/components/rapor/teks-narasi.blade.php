{{-- A stored narrative, printed the same way on every template.

     Blank lines separate blocks. Inside a block, a line starting with a number or
     a dash opens a list item, and the plain lines after it are that item's
     description, as on the Kokurikuler page of docs/contoh-rapor.pdf. Marked up
     line by line because dompdf cannot hang a description under its number. --}}
@props(['teks' => null, 'kosong' => 'Belum ada narasi.'])

@php
    $blok = array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/u', trim((string) $teks)))));
@endphp

@forelse ($blok as $isi)
    @php $dalamDaftar = false; @endphp
    @foreach (preg_split('/\R/u', $isi) as $baris)
        @php
            $baris = trim($baris);
            $butir = preg_match('/^(\d+[.)]|[-•])\s+/u', $baris) === 1;
            $dalamDaftar = $dalamDaftar || $butir;
        @endphp
        <p class="{{ $butir ? 'narasi-butir' : ($dalamDaftar ? 'narasi-rincian' : 'mb-2') }}">{{ $baris }}</p>
    @endforeach
@empty
    <p class="text-[#888]">{{ $kosong }}</p>
@endforelse

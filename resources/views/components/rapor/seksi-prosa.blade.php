{{-- Prose section, used by both A. INTRAKURIKULER and B. KOKURIKULER: a heading bar,
     the stored narrative printed paragraph by paragraph, then a three-photo strip.
     The narrative is the page's full text, including any numbered dimension list.

     The photo strip is a table rather than a CSS grid because dompdf, which renders
     the export, does not lay out grid containers. --}}
@props(['narasi', 'mapel', 'judul' => null, 'untukPdf' => false])

<section class="mb-6 break-inside-avoid">
    <p class="kepala-seksi border border-[#333] bg-[#dfe8f5] px-2 py-1 text-center text-[13px] font-bold uppercase">
        {{ $judul ?? $mapel->Nama_Mapel }}
    </p>

    <div class="isi-seksi border border-t-0 border-[#333] px-3 py-3 text-[13px] leading-relaxed text-justify">
        @php
            $teks = $narasi?->Status_Narasi === \App\Enums\StatusNarasi::Final
                ? $narasi?->Narasi_Final
                : $narasi?->Draft_Narasi;
        @endphp

        @forelse (preg_split('/\n{2,}/', (string) $teks) as $paragraf)
            @if (trim($paragraf) !== '')
                <p class="mb-2 whitespace-pre-line last:mb-0">{{ trim($paragraf) }}</p>
            @endif
        @empty
            <p class="text-[#888]">Belum ada narasi untuk mata pelajaran ini.</p>
        @endforelse

        @if ($narasi?->dokumentasi->isNotEmpty())
            <p class="mt-3 mb-2">Foto kegiatan :</p>
            <table class="foto-strip w-full border-collapse">
                <tbody>
                    <tr>
                        @foreach ($narasi->dokumentasi->take(3) as $foto)
                            <td class="w-1/3 border border-[#333] p-2 align-middle">
                                <img src="{{ $untukPdf ? $foto->berkasAbsolut() : $foto->url() }}"
                                     alt="{{ $foto->Keterangan }}"
                                     style="width: 100%; height: 150px; object-fit: cover;">
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        @endif
    </div>
</section>

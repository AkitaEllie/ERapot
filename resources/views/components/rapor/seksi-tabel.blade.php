{{-- Table template: used by muatan_lokal subjects (Bahasa, Komputer, Budaya Humanis).
     Shows every indicator with its Capaian Akhir, then the subject teacher's Catatan. --}}
@props(['narasi', 'mapel', 'rapor', 'pengampu', 'untukPdf' => false])

@php
    use App\Models\ProgramPengembangan;

    $programs = ProgramPengembangan::query()
        ->where('Mapel_ID', $mapel->getKey())
        ->with(['indikatorCapaian' => fn ($q) => $q->orderBy('Kode_KD')])
        ->orderBy('Nama_Program')
        ->get();

    $nilai = ($narasi?->nilaiSiswa ?? collect())->keyBy('Indikator_ID');

    // Capaian keseluruhan is the lowest level reached, so a subject with any MB
    // is reported as still developing.
    $semua = ($narasi?->nilaiSiswa ?? collect())->map(fn ($n) => $n->Nilai->value);
    $keseluruhan = $semua->contains('MB') ? 'MB' : ($semua->contains('BSH') ? 'BSH' : ($semua->isEmpty() ? '-' : 'BSB'));
    $ejaan = ['MB' => 'Mulai Berkembang', 'BSH' => 'Berkembang Sesuai Harapan', 'BSB' => 'Berkembang Sangat Baik'];

    $foto = $narasi?->dokumentasi->first();
@endphp

<section class="mb-8 break-inside-avoid">
    <header class="mb-3 text-center">
        <h2 class="judul-halaman text-[15px] font-bold uppercase">Laporan Perkembangan Peserta Didik</h2>
        <p class="text-[13px] font-semibold uppercase">Semester {{ $rapor->Semester->label() }}</p>
    </header>

    <table class="w-full border-collapse text-[13px]">
        <tbody>
            <tr>
                <td class="border-0 p-0">Nama : <span class="font-semibold">{{ $rapor->siswa->Nama }}</span></td>
                <td class="border-0 p-0 text-right">Kelas : <span class="font-semibold">{{ $rapor->kelas->Nama_Kelas }}</span></td>
            </tr>
        </tbody>
    </table>

    <p class="mb-1 text-[13px] font-bold uppercase">{{ $mapel->Nama_Mapel }}</p>

    <table class="w-full border-collapse text-[12px]">
        <thead>
            <tr class="baris-keterangan bg-[#f2f2f2] text-center">
                <th class="w-[36px] border border-[#333] px-1 py-1.5 font-bold">No</th>
                <th class="w-[150px] border border-[#333] px-2 py-1.5 font-bold">Program Pengembangan</th>
                <th class="border border-[#333] px-2 py-1.5 font-bold">KD dan Indikator</th>
                <th class="w-[80px] border border-[#333] px-1 py-1.5 font-bold">Capaian Akhir</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($programs as $program)
                @foreach ($program->indikatorCapaian as $indikator)
                    <tr>
                        <td class="border border-[#333] px-1 py-1.5 text-center">{{ $loop->iteration }}</td>
                        @if ($loop->first)
                            <td class="border border-[#333] px-2 py-1.5 text-center font-semibold" rowspan="{{ $program->indikatorCapaian->count() }}">
                                {{ $program->Nama_Program }}
                            </td>
                        @endif
                        <td class="border border-[#333] px-2 py-1.5 whitespace-pre-line">{{ $indikator->Deskripsi }}</td>
                        <td class="border border-[#333] px-1 py-1.5 text-center font-semibold">
                            {{ $nilai->get($indikator->getKey())?->Nilai?->value ?? '-' }}
                        </td>
                    </tr>
                @endforeach
            @endforeach

            @if ($programs->isEmpty())
                <tr>
                    <td colspan="4" class="border border-[#333] px-2 py-4 text-center text-[#888]">
                        Belum ada indikator untuk mata pelajaran ini.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- The Catatan sits beside its photo; expressed as a table because dompdf
         does not lay out flex containers. --}}
    <table class="catatan w-full border-collapse border border-t-0 border-[#333]">
        <tbody>
            <tr>
                <td class="border-0 px-3 py-3 align-top">
                    <p class="mb-1 font-bold">Catatan :</p>
                    <p class="mb-2">
                        Capaian perkembangan {{ explode(' ', $rapor->siswa->Nama)[0] }} secara keseluruhan pada semester
                        {{ strtolower($rapor->Semester->label()) }} ini {{ $ejaan[$keseluruhan] ?? 'belum diisi' }}
                        ({{ $keseluruhan }}).
                    </p>
                    @php
                        $teksCatatan = $narasi?->Status_Narasi === \App\Enums\StatusNarasi::Final
                            ? $narasi?->Narasi_Final
                            : $narasi?->Draft_Narasi;
                    @endphp
                    @forelse (preg_split('/\n{2,}/', (string) $teksCatatan) as $paragraf)
                        @if (trim($paragraf) !== '')
                            <p class="mb-2 last:mb-0 text-justify whitespace-pre-line">{{ trim($paragraf) }}</p>
                        @endif
                    @empty
                        <p class="text-[#888]">Belum ada catatan.</p>
                    @endforelse
                </td>

                @if ($foto !== null)
                    <td class="w-[160px] border-0 px-3 py-3 align-top">
                        <img src="{{ $untukPdf ? $foto->berkasAbsolut() : $foto->url() }}"
                             alt="{{ $foto->Keterangan }}"
                             style="width: 150px; height: 150px; object-fit: cover; border: 1px solid #333;">
                    </td>
                @endif
            </tr>
        </tbody>
    </table>

    <table class="w-full border-collapse text-[12px]">
        <tbody>
            <tr>
                <td class="w-[110px] border border-[#333] bg-[#f2f2f2] px-2 py-4 text-center font-bold" rowspan="3">Keterangan</td>
                <td class="w-[180px] border border-[#333] px-2 py-1 text-center font-bold">MB</td>
                <td class="border border-[#333] px-2 py-1">Mulai Berkembang</td>
            </tr>
            <tr>
                <td class="border border-[#333] px-2 py-1 text-center font-bold">BSH</td>
                <td class="border border-[#333] px-2 py-1">Berkembang Sesuai Harapan</td>
            </tr>
            <tr>
                <td class="border border-[#333] px-2 py-1 text-center font-bold">BSB</td>
                <td class="border border-[#333] px-2 py-1">Berkembang Sangat Baik</td>
            </tr>

            <tr>
                <td class="w-[110px] border border-[#333] bg-[#f2f2f2] px-2 py-4 text-center font-bold" rowspan="2">Tanda Tangan</td>
                <td class="border border-[#333] px-2 py-1 text-center font-bold">Guru {{ $mapel->Nama_Mapel }}</td>
                <td class="border border-[#333] px-2 py-1 text-center">{{ $pengampu?->Nama ?? '' }}</td>
            </tr>
            <tr>
                <td class="border border-[#333] px-2 py-8 text-center font-bold align-top">Orang Tua / Wali</td>
                <td class="border border-[#333] px-2 py-8"></td>
            </tr>
        </tbody>
    </table>

    <div class="mt-4 text-right text-[13px] leading-relaxed">
        <p>Jakarta, {{ optional($rapor->Tanggal_Cetak)->translatedFormat('j F Y') ?? now()->translatedFormat('j F Y') }}</p>
        <p>Ka KB/TK Cinta Kasih Tzu Chi</p>
        <p class="mt-10">Sandra Devi, S.Pd., M.M.</p>
    </div>
</section>


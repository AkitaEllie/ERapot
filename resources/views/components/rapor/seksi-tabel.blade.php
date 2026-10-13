{{-- Table template: used by muatan_lokal subjects (Bahasa, Komputer, Budaya Humanis).
     Shows every indicator with its Capaian Akhir, then the subject teacher's Catatan. --}}
@props(['narasi', 'mapel', 'rapor', 'pengampu'])

@php
    use App\Models\ProgramPengembangan;

    $programs = ProgramPengembangan::query()
        ->where('Mapel_ID', $mapel->getKey())
        ->with(['indikatorCapaian' => fn ($q) => $q->orderBy('Kode_KD')])
        ->orderBy('Nama_Program')
        ->get();

    $nilai = ($narasi?->nilaiSiswa ?? collect())->keyBy('Indikator_ID');

    $perTingkat = fn ($label) => ($narasi?->nilaiSiswa ?? collect())
        ->firstWhere('Nilai.value', $label);

    // Capaian keseluruhan is the lowest level reached, so a subject with any MB
    // is reported as still developing.
    $semua = ($narasi?->nilaiSiswa ?? collect())->map(fn ($n) => $n->Nilai->value);
    $keseluruhan = $semua->contains('MB') ? 'MB' : ($semua->contains('BSH') ? 'BSH' : ($semua->isEmpty() ? '-' : 'BSB'));
    $ejaan = ['MB' => 'Mulai Berkembang', 'BSH' => 'Berkembang Sesuai Harapan', 'BSB' => 'Berkembang Sangat Baik'];
@endphp

<section class="mb-8 break-inside-avoid">
    <header class="mb-3 text-center">
        <h2 class="text-[15px] font-bold uppercase">Laporan Perkembangan Peserta Didik</h2>
        <p class="text-[13px] font-semibold uppercase">Semester {{ $rapor->Semester->label() }}</p>
    </header>

    <div class="mb-2 flex justify-between text-[13px] font-semibold">
        <span>Nama : {{ $rapor->siswa->Nama }}</span>
        <span>Kelas : {{ $rapor->kelas->Nama_Kelas }}</span>
    </div>

    <p class="mb-1 text-[13px] font-bold uppercase">{{ $mapel->Nama_Mapel }}</p>

    <table class="w-full border-collapse text-[12px]">
        <thead>
            <tr class="bg-[#f2f2f2] text-center">
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
                        <td class="border border-[#333] px-2 py-1.5">{{ $indikator->Deskripsi }}</td>
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

    <div class="border border-t-0 border-[#333] p-3">
        <div class="flex gap-4">
            <div class="flex-1 text-[13px] leading-relaxed">
                <p class="mb-1 font-bold">Catatan :</p>
                <p class="mb-2">
                    Capaian perkembangan {{ explode(' ', $rapor->siswa->Nama)[0] }} secara keseluruhan pada semester
                    {{ strtolower($rapor->Semester->label()) }} ini {{ $ejaan[$keseluruhan] ?? 'belum diisi' }}
                    ({{ $keseluruhan }}).
                </p>
                @php
                    $catatan = $narasi?->Status_Narasi === \App\Enums\StatusNarasi::Final
                        ? $narasi?->Narasi_Final
                        : $narasi?->Draft_Narasi;
                @endphp
                @forelse (preg_split('/\n{2,}/', (string) $catatan) as $paragraf)
                    @if (trim($paragraf) !== '')
                        <p class="mb-2 last:mb-0 text-justify">{{ trim($paragraf) }}</p>
                    @endif
                @empty
                    <p class="text-[#888]">Belum ada catatan.</p>
                @endforelse
            </div>

            @if ($narasi?->dokumentasi->isNotEmpty())
                <div class="w-[150px] shrink-0">
                    <img src="{{ Illuminate\Support\Facades\Storage::url($narasi->dokumentasi->first()->Foto) }}"
                         alt="{{ $narasi->dokumentasi->first()->Keterangan }}"
                         class="h-[150px] w-[150px] border border-[#333] object-cover">
                </div>
            @endif
        </div>
    </div>

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

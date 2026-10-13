{{-- Identity block: the cover's centred heading and bordered identity table, as on page 1
     of docs/contoh-rapor.pdf. --}}
<header class="mb-4 text-center leading-tight">
    <h1 class="text-[15px] font-bold uppercase">Laporan Perkembangan Hasil Belajar</h1>
    <p class="text-[15px] font-bold uppercase">{{ $rapor->kelas->Jenjang->label() }} Cinta Kasih Tzu Chi</p>
    <p class="text-[15px] font-bold uppercase">
        Semester {{ $rapor->Semester->label() }} TA. {{ $rapor->kelas->tahunAjaran->Tahun_Ajaran }}
    </p>
</header>

@php
    // The ERD stores one growth status for the whole report, so it is printed
    // beside each measurement rather than tracked per measurement.
    $status = $rapor->Status_Pertumbuhan;
@endphp

<table class="w-full border-collapse text-[13px]">
    <tbody>
        <tr>
            <td class="w-[110px] border border-[#333] px-2 py-1 font-medium">Nama Sekolah</td>
            <td class="w-[220px] border border-[#333] px-2 py-1">TK Cinta Kasih Tzu Chi</td>
            <td class="w-[110px] border border-[#333] px-2 py-1 font-medium">Kelas</td>
            <td class="border border-[#333] px-2 py-1">{{ $rapor->kelas->Nama_Kelas }}</td>
        </tr>
        <tr>
            <td class="border border-[#333] px-2 py-1 font-medium">Nama Siswa</td>
            <td class="border border-[#333] px-2 py-1">{{ $rapor->siswa->Nama }}</td>
            <td class="border border-[#333] px-2 py-1 font-medium">Fase</td>
            <td class="border border-[#333] px-2 py-1">{{ $rapor->kelas->Fase }}</td>
        </tr>
        <tr>
            <td class="border border-[#333] px-2 py-1 font-medium">Tahun Ajaran</td>
            <td class="border border-[#333] px-2 py-1">{{ $rapor->kelas->tahunAjaran->Tahun_Ajaran }}</td>
            <td class="border border-[#333] px-2 py-1 font-medium">Tinggi Badan</td>
            <td class="border border-[#333] px-2 py-1">{{ $rapor->Tinggi_Badan }} cm{{ $status ? ' ('.$status.')' : '' }}</td>
        </tr>
        <tr>
            <td class="border border-[#333] px-2 py-1 font-medium">Semester</td>
            <td class="border border-[#333] px-2 py-1">{{ $rapor->Semester->label() }}</td>
            <td class="border border-[#333] px-2 py-1 font-medium">Berat Badan</td>
            <td class="border border-[#333] px-2 py-1">{{ $rapor->Berat_Badan }} kg{{ $status ? ' ('.$status.')' : '' }}</td>
        </tr>
        <tr>
            <td class="border border-[#333] px-2 py-1 font-medium">Lingkar Kepala</td>
            <td class="border border-[#333] px-2 py-1">{{ $rapor->Lingkar_Kepala }} cm{{ $status ? ' ('.$status.')' : '' }}</td>
            <td class="border border-[#333] px-2 py-1"></td>
            <td class="border border-[#333] px-2 py-1"></td>
        </tr>
    </tbody>
</table>

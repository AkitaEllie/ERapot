{{-- Refleksi orang tua: a printed form for handwriting, so the boxes stay empty.
     The blank space uses an inline height because Tailwind's h-[110px] has no
     effect in the PDF, where the compiled bundle is absent. --}}
@props(['rapor'])

<section class="mb-6 break-inside-avoid">
    <p class="mb-2 font-bold">Refleksi orang tua:</p>

    <div class="border border-[#333] p-2 text-[13px]">
        <p>1. Apa yang sudah berkembang yang saya amati dari anak ?</p>
        <div style="height: 110px;"></div>
    </div>

    <div class="border border-t-0 border-[#333] p-2 text-[13px]">
        <p>2. Apa yang perlu dikembangkan dari anak menurut yang saya amati ?</p>
        <div style="height: 110px;"></div>
    </div>
</section>

<table class="w-full border-collapse text-[13px]">
    <tbody>
        <tr class="text-center font-bold">
            <td class="w-[200px] border border-[#333] px-2 py-1" rowspan="2">Kehadiran</td>
            <td class="border border-[#333] px-2 py-1" colspan="2">Tanda Tangan</td>
        </tr>
        <tr class="text-center font-bold">
            <td class="border border-[#333] px-2 py-1">Guru kelas</td>
            <td class="border border-[#333] px-2 py-1">Orang Tua / Wali</td>
        </tr>
        <tr>
            <td class="border border-[#333] px-2 py-1 align-top">
                <p>Sakit : {{ $rapor->Sakit }} Hari</p>
                <p>Izin : {{ $rapor->Izin }} Hari</p>
                <p>Tanpa keterangan : {{ $rapor->Tanpa_Keterangan }} Hari</p>
            </td>
            <td class="border border-[#333] px-2 py-10 text-center align-bottom">
                ( {{ $rapor->kelas->waliKelas?->Nama ?? '                              ' }} )
            </td>
            <td class="border border-[#333] px-2 py-10 text-center align-bottom">(                              )</td>
        </tr>
    </tbody>
</table>

<div class="mt-6 text-right text-[13px] leading-relaxed">
    <p>Jakarta, {{ optional($rapor->Tanggal_Cetak)->translatedFormat('j F Y') ?? now()->translatedFormat('j F Y') }}</p>
    <p>Ka KB/TK Cinta Kasih Tzu Chi</p>
    <p class="mt-10">Sandra Devi, S.Pd., M.M.</p>
</div>

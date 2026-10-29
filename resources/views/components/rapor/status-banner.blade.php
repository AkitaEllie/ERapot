@props(['rapor'])

{{-- What the teacher needs to know about the report's state before editing it:
     the principal's revision note, or that it is locked for review. --}}
@if ($rapor->Status === \App\Enums\RaporStatus::PerluRevisi && filled($rapor->Catatan_Revisi))
    <div class="mx-8 mt-6 rounded border border-rose-200 bg-rose-50 px-5 py-4" role="note">
        <p class="text-[14px] font-semibold text-rose-900">Catatan revisi dari Kepala Sekolah</p>
        <p class="mt-1 text-[15px] text-rose-900">"{{ $rapor->Catatan_Revisi }}"</p>
        <p class="mt-1.5 text-[13px] text-rose-800">Perbaiki bagian yang diminta, lalu tekan Ajukan Ulang.</p>
    </div>
@elseif ($rapor->Status === \App\Enums\RaporStatus::Menunggu)
    <div class="mx-8 mt-6 rounded border border-amber-200 bg-amber-50 px-5 py-3 text-[14px] text-amber-900" role="note">
        Rapor sedang menunggu persetujuan Kepala Sekolah dan tidak dapat diubah sampai ada keputusan.
    </div>
@elseif ($rapor->Status === \App\Enums\RaporStatus::Disetujui)
    <div class="mx-8 mt-6 rounded border border-accent/40 bg-accent-soft px-5 py-3 text-[14px] text-accent-deep" role="note">
        Rapor sudah disetujui Kepala Sekolah{{ $rapor->Tanggal_Persetujuan ? ' pada '.$rapor->Tanggal_Persetujuan->translatedFormat('j F Y') : '' }} dan tidak dapat diubah lagi.
    </div>
@endif

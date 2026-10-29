@props(['rapor', 'modal', 'kekurangan'])

{{-- Dialogs of the submit flow, wireframes G-03a and G-03b. --}}
@if ($modal === 'kurang')
    <x-ui.modal title="Rapor belum dapat diajukan" subtitle="Lengkapi bagian berikut terlebih dahulu, lalu tekan Ajukan Rapor kembali." close="tutupModalAjukan">
        <ul class="divide-y divide-hairline overflow-hidden rounded border border-hairline">
            @foreach ($kekurangan as $item)
                <li class="flex items-center gap-4 px-4 py-3">
                    <div class="min-w-0 flex-1 leading-tight">
                        <p class="text-[15px] font-medium text-heading">{{ $item['bagian'] }}</p>
                        <p class="text-[13px] text-muted">{{ $item['keterangan'] }}</p>
                    </div>
                    <x-ui.button-link size="sm" :href="$item['mapel'] ? route('rapor.input', ['rapor' => $rapor->getKey(), 'mapel' => $item['mapel']]) : route('rapor.data-fisik', $rapor)">Lengkapi</x-ui.button-link>
                </li>
            @endforeach
        </ul>

        <x-slot:footer>
            <x-ui.button type="button" variant="primary" wire:click="tutupModalAjukan">Tutup</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
@elseif ($modal === 'konfirmasi')
    <x-ui.modal title="Ajukan rapor ke Kepala Sekolah?" subtitle="Setelah diajukan, rapor berstatus Menunggu Persetujuan dan tidak dapat diubah sampai Kepala Sekolah memberi keputusan." close="tutupModalAjukan">
        <ul class="space-y-2.5 rounded border border-hairline px-4 py-4 text-[15px] text-body">
            @foreach (['Nilai indikator seluruh mata pelajaran lengkap', 'Narasi seluruh mata pelajaran tersedia', 'Foto dokumentasi terunggah', 'Data fisik & kehadiran terisi'] as $baris)
                <li class="flex items-center gap-2.5">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0 text-accent-deep" aria-hidden="true"><path d="M4.5 10.5 8 14l7.5-8"/></svg>
                    {{ $baris }}
                </li>
            @endforeach
        </ul>

        <x-slot:footer>
            <x-ui.button type="button" variant="secondary" wire:click="tutupModalAjukan">Batal</x-ui.button>
            <x-ui.button type="button" variant="primary" wire:click="konfirmasiAjukan">Ajukan Rapor</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
@endif

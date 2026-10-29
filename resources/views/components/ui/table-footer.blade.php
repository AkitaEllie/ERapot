@props(['paginator', 'satuan' => 'data'])

{{-- "Menampilkan x dari y" plus previous/next, matching the existing list screens. --}}
@if ($paginator->total() > 0)
    <div class="flex items-center justify-between border-t border-hairline px-4 py-3 text-[14px] text-muted">
        <p>Menampilkan {{ $paginator->count() }} dari {{ $paginator->total() }} {{ $satuan }}</p>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" wire:click="previousPage" :disabled="$paginator->onFirstPage()">Sebelumnya</x-ui.button>
                <span class="px-1 text-[13px]">Halaman {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
                <x-ui.button type="button" variant="secondary" size="sm" wire:click="nextPage" :disabled="! $paginator->hasMorePages()">Berikutnya</x-ui.button>
            </div>
        @endif
    </div>
@endif

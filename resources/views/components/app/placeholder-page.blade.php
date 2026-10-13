@props([
    'title',
    'subtitle' => null,
    'figmaScreen' => null,
    'method' => null,
])

{{-- Shared shell for screens that a menu entry names but the wireframe never designed.
     Deliberately inert: no forms, no queries, no actions. --}}
<div>
    <x-app.header :title="$title" :subtitle="$subtitle" />

    <div class="flex-1 overflow-y-auto bg-canvas px-8 py-6">
        <div class="max-w-[720px] rounded border border-dashed border-hairline-strong bg-surface p-8">
            <div class="flex items-start gap-4">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-notice-bg text-label">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" class="size-5" aria-hidden="true">
                        <circle cx="10" cy="10" r="7.5"/><path d="M10 6v4.5M10 13.6h.01"/>
                    </svg>
                </span>

                <div class="min-w-0">
                    <h2 class="text-[16px] font-semibold text-heading">Layar ini belum dibuat</h2>
                    <p class="mt-1.5 text-[15px] leading-relaxed text-body">
                        Menu <span class="font-medium text-heading">{{ $title }}</span> disebutkan di sidebar
                        wireframe, tetapi isinya belum dirancang dan belum diimplementasikan.
                    </p>

                    <dl class="mt-5 space-y-2.5 text-[14px]">
                        @if ($figmaScreen)
                            <div class="flex gap-3">
                                <dt class="w-40 shrink-0 text-muted">Frame wireframe</dt>
                                <dd class="text-body">{{ $figmaScreen }}</dd>
                            </div>
                        @endif

                        @if ($method)
                            <div class="flex gap-3">
                                <dt class="w-40 shrink-0 text-muted">Method terkait</dt>
                                <dd class="min-w-0 break-words font-mono text-[13px] text-body">{{ $method }}</dd>
                            </div>
                        @endif

                        <div class="flex gap-3">
                            <dt class="w-40 shrink-0 text-muted">Referensi</dt>
                            <dd class="text-body">docs/CLASS-DIAGRAM-GAP.md (B2, B3, B4)</dd>
                        </div>
                    </dl>

                    <p class="mt-5 text-[14px] text-muted">
                        Halaman ini sengaja tidak memiliki fungsi apa pun supaya statusnya jelas.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

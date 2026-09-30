@props(['value' => 0, 'width' => 'w-45'])

<div class="h-2 {{ $width }} overflow-hidden rounded-full bg-sunken">
    <div class="h-full rounded-full bg-accent" style="width: {{ max(0, min(100, $value)) }}%"></div>
</div>

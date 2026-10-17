@props(['label', 'value', 'hint' => null])
<div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
    <p class="text-xs font-bold uppercase tracking-wider text-white/70">{{ $label }}</p>
    <p class="mt-2 text-3xl font-black tabular-nums">{{ $value }}</p>
    @if($hint)<p class="mt-1 text-xs text-white/70">{{ $hint }}</p>@endif
</div>

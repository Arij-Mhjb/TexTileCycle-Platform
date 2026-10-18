@props(['label', 'value', 'hint' => null])
<div class="px-7 py-5 sm:px-10">
    <p class="mod-display text-3xl font-bold tabular-nums sm:text-[2.35rem]">{{ $value }}</p>
    <p class="mt-1 text-[13px] font-medium text-white/60">{{ $label }}@if($hint)<span class="text-white/40"> · {{ $hint }}</span>@endif</p>
</div>

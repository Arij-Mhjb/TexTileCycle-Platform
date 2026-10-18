@props(['label', 'value', 'hint' => null])
<div class="px-7 py-5 sm:px-10">
    <p class="mod-display text-[1.65rem] font-bold leading-tight tabular-nums sm:text-[2rem]">{{ $value }}</p>
    <p class="mt-1 text-[13px] font-medium text-white/60">{{ $label }}@if($hint)<span class="text-white/40"> · {{ $hint }}</span>@endif</p>
</div>

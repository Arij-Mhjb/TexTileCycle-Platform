@props(['label', 'value', 'hint' => null])
<div class="mod-tile rounded-2xl border border-white/20 bg-white/[.12] p-4 backdrop-blur-md transition duration-300 hover:-translate-y-1">
    <p class="text-[13px] font-semibold text-white/75">{{ $label }}</p>
    <p class="mt-1.5 text-3xl font-black tabular-nums tracking-tight">{{ $value }}</p>
    @if($hint)<p class="mt-1 text-xs text-white/65">{{ $hint }}</p>@endif
</div>

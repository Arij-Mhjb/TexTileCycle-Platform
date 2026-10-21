@aware(['module' => 'suppliers'])
@props(['label', 'value', 'hint' => null])
@php $accent = ['suppliers' => 'text-fuchsia-200', 'esg' => 'text-sky-200', 'loop' => 'text-teal-200'][$module] ?? null; @endphp
@if($accent)
    <div class="min-w-[9rem] rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</p>
        <p class="mt-2 text-xl font-black tabular-nums {{ $accent }}">{{ $value }}</p>
        @if($hint)<p class="mt-0.5 text-[11px] text-slate-400">{{ $hint }}</p>@endif
    </div>
@else
    <div class="px-7 py-5 sm:px-10">
        <p class="mod-display text-[1.65rem] font-bold leading-tight tabular-nums sm:text-[2rem]">{{ $value }}</p>
        <p class="mt-1 text-[13px] font-medium text-white/60">{{ $label }}@if($hint)<span class="text-white/40"> · {{ $hint }}</span>@endif</p>
    </div>
@endif

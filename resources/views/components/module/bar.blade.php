@props(['module' => 'esg', 'value' => 0, 'max' => 100, 'label' => null, 'display' => null])
@php
$fill = ['suppliers' => 'from-purple-600 to-pink-500', 'esg' => 'from-sky-500 to-cyan-400', 'repair' => 'from-amber-500 to-orange-400'][$module];
$percent = $max > 0 ? max(0, min(100, $value / $max * 100)) : 0;
@endphp
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if($label)<div class="flex items-center justify-between gap-3 text-xs"><span class="font-semibold text-slate-500">{{ $label }}</span><span class="font-black tabular-nums text-slate-700 dark:text-slate-200">{{ $display ?? $value }}</span></div>@endif
    <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" role="meter" aria-valuemin="0" aria-valuemax="{{ $max }}" aria-valuenow="{{ $value }}" @if($label) aria-label="{{ $label }}" @endif>
        <div class="h-full rounded-full bg-gradient-to-r {{ $fill }}" style="width: {{ $percent }}%"></div>
    </div>
</div>

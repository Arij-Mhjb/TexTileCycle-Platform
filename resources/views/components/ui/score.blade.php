@props(['label', 'value' => null])
<div class="space-y-2">
    <div class="flex items-center justify-between gap-3 text-xs"><span class="text-slate-500 dark:text-slate-400">{{ $label }}</span><span class="font-bold tabular-nums">{{ $value === null ? 'Non évalué' : $value.'/100' }}</span></div>
    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" @if($value !== null) role="meter" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $value }}" @else aria-hidden="true" @endif>
        <div class="h-full rounded-full bg-gradient-to-r from-teal-600 to-emerald-400" style="width: {{ max(0, min(100, $value ?? 0)) }}%"></div>
    </div>
</div>

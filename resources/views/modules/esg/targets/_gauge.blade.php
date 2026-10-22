{{-- Jauge de consommation du budget carbone. Variables : $status --}}
@php
$usage = $status['usage'];
$after = $status['after_levers_usage'];
$color = ['over' => 'bg-rose-500', 'warning' => 'bg-amber-500', 'ok' => 'bg-sky-500'][$status['state']];
$tone = ['over' => 'bg-rose-50 text-rose-700 ring-rose-200', 'warning' => 'bg-amber-50 text-amber-800 ring-amber-200', 'ok' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'][$status['state']];
$kg = fn ($value) => number_format((float) $value, 0, ',', ' ');
@endphp
<div>
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <p class="text-sm text-slate-500"><strong class="text-2xl font-black tabular-nums text-slate-900 dark:text-white">{{ $kg($status['total_kg']) }}</strong> kg CO₂e prévus @if($status['budget_kg']) sur <strong class="text-slate-700 dark:text-slate-200">{{ $kg($status['budget_kg']) }}</strong> @endif</p>
        <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $tone }}">{{ $status['state_label'] }}@if($usage !== null) · {{ $usage }} %@endif</span>
    </div>
    @if($usage !== null)
        <div class="relative mt-3 h-3 rounded-full bg-slate-100 dark:bg-slate-800" role="img" aria-label="{{ $usage }} % du budget utilisé">
            <div class="h-3 rounded-full {{ $color }}" style="width: {{ min(100, max(2, $usage)) }}%"></div>
            @if($after !== null && $status['savings_kg'] > 0)
                <div class="absolute top-[-4px] h-5 w-0.5 rounded bg-slate-900 dark:bg-white" style="left: {{ min(100, $after) }}%" title="Après leviers : {{ $after }} %"></div>
            @endif
        </div>
        @if($status['savings_kg'] > 0)
            <p class="mt-2 text-xs text-slate-500">Trait noir : <strong class="text-sky-700 dark:text-sky-300">{{ $after }} %</strong> du budget si vous passez les matières vierges en recyclé (−{{ $kg($status['savings_kg']) }} kg CO₂e).</p>
        @endif
    @endif
</div>

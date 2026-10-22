{{-- Une boucle dans une liste. Variables : $cycle, $space, $highlight (bool) --}}
@php
$showRoute = ['brand' => 'brand.recycling.show', 'supplier' => 'supplier.recycling.show', 'admin' => 'admin.recycling.show'][$space];
$kg = fn ($value) => number_format((float) $value, ((float) $value == (int) $value) ? 0 : 1, ',', ' ');
$partner = ['brand' => $cycle->supplier?->name, 'supplier' => $cycle->brand?->name, 'admin' => $cycle->brand?->name.' → '.$cycle->supplier?->name][$space];
$phase = $cycle->phaseIndex();
$out = $cycle->output_kg ?: $cycle->expected_output_kg;
@endphp
<a href="{{ route($showRoute, $cycle) }}"
   class="group flex flex-wrap items-center gap-x-6 gap-y-3 rounded-2xl border bg-white px-5 py-4 transition hover:border-teal-300 hover:shadow-[0_14px_30px_-20px_rgba(13,148,136,.6)] dark:bg-slate-900 {{ $highlight ? 'border-teal-300 dark:border-teal-700' : 'border-slate-200 dark:border-slate-800' }}">
    {{-- 4 temps de la boucle --}}
    <span class="flex items-center gap-1" aria-label="Étape {{ min(4, $phase + 1) }} sur 4">
        @foreach(range(0, 3) as $i)
            <span class="h-2 w-6 rounded-full {{ $cycle->status === 'declined' ? 'bg-slate-200 dark:bg-slate-700' : ($i < $phase ? 'bg-teal-600' : ($i === $phase ? 'bg-teal-300' : 'bg-slate-200 dark:bg-slate-700')) }}"></span>
        @endforeach
    </span>
    <span class="min-w-0 flex-1">
        <span class="flex flex-wrap items-baseline gap-x-2">
            <span class="font-black text-slate-900 group-hover:text-teal-800 dark:text-white">{{ $partner }}</span>
            <span class="text-xs text-slate-400">{{ $cycle->reference }} · {{ $cycle->created_at->format('d/m/Y') }}</span>
        </span>
        <span class="block truncate text-sm text-slate-500">{{ $cycle->situation($space) }}</span>
    </span>
    <span class="flex items-center gap-2 text-sm font-bold tabular-nums">
        <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $kg($cycle->received_kg ?: $cycle->totalKg()) }} kg</span>
        <svg class="h-4 w-4 text-teal-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
        <span class="rounded-lg px-2.5 py-1 {{ $out ? 'bg-teal-50 text-teal-800 dark:bg-teal-950 dark:text-teal-200' : 'bg-slate-50 text-slate-400 dark:bg-slate-800' }}">{{ $out ? $kg($out).' kg' : '? kg' }}</span>
        @if($cycle->amount() !== null && $cycle->status !== 'declined')
            <span class="hidden rounded-lg px-2.5 py-1 text-xs text-slate-500 ring-1 ring-slate-200 dark:ring-slate-700 md:inline" title="{{ $cycle->isBuyback() ? 'Avoir pour la marque' : 'Frais de recyclage' }}">{{ $cycle->isBuyback() ? 'avoir' : 'frais' }} {{ number_format(abs($cycle->amount()), 0, ',', ' ') }} TND</span>
        @endif
    </span>
    <span class="w-full text-right sm:w-56">
        @if($highlight && $cycle->nextAction())
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-teal-700 px-3 py-1.5 text-xs font-bold text-white">{{ $cycle->nextAction() }} →</span>
        @else
            <span class="text-xs font-semibold {{ $cycle->status === 'declined' ? 'text-slate-400' : 'text-teal-700 dark:text-teal-300' }}">{{ $cycle->statusLabel() }}</span>
        @endif
    </span>
</a>

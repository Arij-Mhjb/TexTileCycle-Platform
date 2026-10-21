{{-- La boucle en 4 temps : Accord → Envoi → Recyclage → Retour à la marque (ou avoir), avec les kilos.
     Variables : $cycle (RecyclingLoop), $estimate (array), $compact (bool, optionnel) --}}
@php
$current = $cycle->phaseIndex();
$declined = $cycle->status === 'declined';
$kg = fn ($value) => $value === null ? '—' : number_format((float) $value, ((float) $value == (int) $value) ? 0 : 1, ',', ' ').' kg';
$sent = $cycle->received_kg ?: $cycle->totalKg();
$out = $cycle->output_kg ?: ($cycle->expected_output_kg ?: ($estimate['expected_kg'] ?? null));
$money = fn ($value) => number_format(abs((float) $value), 2, ',', ' ').' TND';
$nodes = [
    ['Accord', $cycle->brand->name.' ⇄ '.$cycle->supplier->name, $cycle->isBuyback() ? 'Rachat par le fournisseur' : 'Récupération de la matière', 'M4 9h14l-3-3M20 15H6l3 3'],
    ['Envoi', 'vers '.$cycle->supplier->name, $kg($sent).' d’articles', 'M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-.01M17 19a2 2 0 1 0 0-.01'],
    ['Recyclage', $cycle->outputMaterial?->name ?? ($estimate['material_name'] ?? 'matière recyclée'), $kg($out).($cycle->output_kg ? '' : ' prévus'), 'M20 12a8 8 0 0 1-13.7 5.6M4 12a8 8 0 0 1 13.7-5.6M18 3v4h-4M6 21v-4h4'],
    $cycle->isBuyback()
        ? ['Avoir à la marque', 'frais de recyclage déduits', $cycle->amount() !== null ? $money($cycle->amount()) : '—', 'M12 3v18M17 7H9.5a3 3 0 0 0 0 6h5a3 3 0 0 1 0 6H6']
        : ['Retour à la marque', $cycle->outputBatch?->batch_code ? 'lot '.$cycle->outputBatch->batch_code : 'nouveau lot traçable', $kg($out).' de matière', 'M4 10l8-6 8 6v9a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1z'],
];
@endphp
<section aria-label="Avancement de la boucle de recyclage" class="relative rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-7">
    <ol class="relative grid gap-4 sm:grid-cols-4 sm:gap-0">
        @foreach($nodes as $index => [$label, $line, $value, $icon])
            @php $done = ! $declined && $index < $current; $active = ! $declined && $index === $current; @endphp
            <li class="relative flex items-center gap-4 sm:flex-col sm:text-center">
                @unless($loop->last)
                    <span class="absolute left-[27px] top-14 h-[calc(100%-2rem)] w-0.5 sm:left-[calc(50%+34px)] sm:top-7 sm:h-0.5 sm:w-[calc(100%-68px)] {{ $done ? 'bg-teal-600' : 'bg-slate-200 dark:bg-slate-700' }}" aria-hidden="true"></span>
                @endunless
                <span class="relative z-10 grid h-14 w-14 shrink-0 place-items-center rounded-2xl transition {{ $done ? 'bg-teal-600 text-white' : ($active ? 'bg-teal-700 text-white ring-[6px] ring-teal-100 dark:ring-teal-900' : 'bg-slate-100 text-slate-400 dark:bg-slate-800') }}">
                    @if($done)
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                    @else
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    @endif
                </span>
                <span class="min-w-0 sm:mt-3">
                    <span class="block text-[11px] font-bold uppercase tracking-wider {{ $active ? 'text-teal-700 dark:text-teal-300' : 'text-slate-400' }}">{{ $index + 1 }} · {{ $label }}</span>
                    <span class="block truncate text-sm font-bold text-slate-900 dark:text-white">{{ $value }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $line }}</span>
                </span>
            </li>
        @endforeach
    </ol>
    {{-- Le fil qui revient au point de départ : la matière revient à la marque. --}}
    <svg class="pointer-events-none mt-3 hidden h-8 w-full sm:block" viewBox="0 0 1000 40" preserveAspectRatio="none" fill="none" aria-hidden="true">
        <path d="M 875 2 C 875 34, 860 36, 820 36 L 180 36 C 140 36, 125 34, 125 8" stroke="{{ $current >= 4 ? '#0d9488' : '#cbd5e1' }}" stroke-width="2" stroke-dasharray="6 6" vector-effect="non-scaling-stroke"/>
        <path d="M 118 14 L 125 4 L 132 14" stroke="{{ $current >= 4 ? '#0d9488' : '#cbd5e1' }}" stroke-width="2" vector-effect="non-scaling-stroke"/>
    </svg>
</section>

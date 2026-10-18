{{-- Détail d'un rapport d'impact, partagé admin / marque. Variables : $report, $stages, $history --}}
@php
$carbon = $report->carbonFootprints->keyBy('stage');
$water = $report->waterFootprints->keyBy('stage');
$weight = max(0.001, (float) $report->product_weight_kg);
@endphp
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    @foreach([
        ['CO₂', number_format((float) $report->carbon_kg, 2, ',', ' '), 'kg CO₂e', number_format($report->carbon_kg / $weight, 1, ',', ' ').' kg / kg produit'],
        ['Eau', number_format((float) $report->water_l, 0, ',', ' '), 'litres', number_format($report->water_l / $weight, 0, ',', ' ').' L / kg'],
        ['Énergie', number_format((float) $report->energy_kwh, 1, ',', ' '), 'kWh', number_format($report->energy_kwh / $weight, 1, ',', ' ').' kWh / kg'],
        ['Déchets', number_format((float) $report->waste_kg, 3, ',', ' '), 'kg', 'dont évités : '.number_format((float) $report->avoided_waste_kg, 2, ',', ' ').' kg'],
        ['Bonus circularité', '+'.$report->circularity_bonus, 'points', 'réparable, réutilisable, recyclable, retours'],
    ] as [$label, $value, $unit, $hint])
        <div class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[13px] font-semibold text-sky-600">{{ $label }}</p>
            <p class="mt-2 text-2xl font-black tabular-nums text-slate-900 dark:text-white">{{ $value }} <span class="text-sm font-semibold text-slate-400">{{ $unit }}</span></p>
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="shadow-[0_1px_2px_rgba(15,23,42,.05)] rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 xl:col-span-2">
        <p class="text-[13px] font-semibold text-sky-600">Cycle de vie</p>
        <h2 class="mt-1 text-lg font-black">Empreinte carbone et eau par étape</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-2">
            <div class="h-64"><canvas aria-label="Empreinte carbone par étape" data-esg-chart="{{ json_encode(['type' => 'bar', 'labels' => array_values($stages), 'datasets' => [['label' => 'kg CO₂e', 'data' => collect(array_keys($stages))->map(fn ($stage) => (float) ($carbon[$stage]->kg_co2e ?? 0))]]]) }}"></canvas></div>
            <div class="h-64"><canvas aria-label="Empreinte eau par étape" data-esg-chart="{{ json_encode(['type' => 'doughnut', 'labels' => array_values($stages), 'datasets' => [['label' => 'Litres', 'data' => collect(array_keys($stages))->map(fn ($stage) => (float) ($water[$stage]->liters ?? 0))]]]) }}"></canvas></div>
        </div>
        <table class="mt-5 min-w-full text-sm">
            <thead><tr class="text-left text-[13px] text-slate-400"><th class="py-2">Étape</th><th class="py-2 text-right">kg CO₂e</th><th class="py-2 text-right">Litres d’eau</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($stages as $stage => $label)
                    <tr><td class="py-2 font-semibold">{{ $label }}</td><td class="py-2 text-right">{{ number_format((float) ($carbon[$stage]->kg_co2e ?? 0), 3, ',', ' ') }}</td><td class="py-2 text-right">{{ number_format((float) ($water[$stage]->liters ?? 0), 0, ',', ' ') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </section>
    <section class="space-y-6">
        <div class="shadow-[0_1px_2px_rgba(15,23,42,.05)] rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[13px] font-semibold text-sky-600">Composition utilisée</p>
            <div class="mt-4 space-y-3">
                @forelse($report->product->materials as $material)
                    <x-module.bar module="esg" :label="$material->name.($material->impactMetrics->isNotEmpty() ? '' : ' (facteur par défaut)')" :value="(float) ($material->pivot->percentage ?? 0)" :display="$material->pivot->percentage !== null ? (float) $material->pivot->percentage.' %' : '—'"/>
                @empty
                    <p class="text-sm text-slate-500">Composition non renseignée : facteurs moyens appliqués.</p>
                @endforelse
            </div>
            <p class="mt-4 text-xs text-slate-400">Poids retenu : {{ (float) $report->product_weight_kg }} kg @if($report->season)· saison {{ $report->season }}@endif</p>
        </div>
        @if($history->count() > 1)
            <div class="shadow-[0_1px_2px_rgba(15,23,42,.05)] rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <p class="text-[13px] font-semibold text-sky-600">Évolution du score</p>
                <div class="mt-3 h-40"><canvas aria-label="Évolution du score" data-esg-chart="{{ json_encode(['type' => 'line', 'labels' => $history->map(fn ($item) => $item->created_at->format('d/m')), 'datasets' => [['label' => 'Score', 'data' => $history->pluck('impact_score')]]]) }}"></canvas></div>
            </div>
        @endif
    </section>
</div>

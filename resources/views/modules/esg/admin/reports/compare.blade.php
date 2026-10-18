<x-app-layout title="Comparer les impacts">
    @vite('resources/js/esg-charts.js')
    <x-module.hero module="esg" title="Comparaison produit / catégorie" description="Sélectionnez jusqu’à 5 produits pour comparer leurs derniers rapports d’impact."/>
    @include('modules.esg.admin._tabs')

    <form class="panel mb-6 p-6">
        <div class="flex flex-wrap gap-2">
            @forelse($products as $product)
                <label class="cursor-pointer rounded-full border px-4 py-2 text-sm font-bold transition has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50 has-[:checked]:text-sky-700 {{ $selected->contains($product->id) ? 'border-sky-500 bg-sky-50 text-sky-700' : 'border-slate-200 text-slate-600' }}">
                    <input type="checkbox" name="products[]" value="{{ $product->id }}" class="sr-only" @checked($selected->contains($product->id))>{{ $product->name }} <span class="font-normal text-slate-400">· {{ $product->category }}</span>
                </label>
            @empty
                <p class="text-sm text-slate-500">Générez d’abord des rapports d’impact.</p>
            @endforelse
        </div>
        <x-module.button module="esg" class="mt-5">Comparer</x-module.button>
    </form>

    @if($reports->isNotEmpty())
        <div class="grid gap-6 xl:grid-cols-2">
            <section class="panel p-6">
                <h2 class="text-lg font-black">Profil par catégorie d’impact</h2>
                <p class="text-xs text-slate-500">Valeurs par kg de produit, normalisées sur 100 (plus haut = plus d’impact).</p>
                @php
                    $metrics = ['carbon_kg' => 'CO₂', 'water_l' => 'Eau', 'energy_kwh' => 'Énergie', 'waste_kg' => 'Déchets'];
                    $intensity = fn ($report, $field) => (float) $report->{$field} / max(0.001, (float) $report->product_weight_kg);
                    $maxima = collect($metrics)->map(fn ($label, $field) => max(0.0001, $reports->max(fn ($report) => $intensity($report, $field))));
                @endphp
                <div class="mt-4 h-80"><canvas aria-label="Comparaison radar" data-esg-chart="{{ json_encode(['type' => 'radar', 'labels' => array_values($metrics), 'datasets' => $reports->map(fn ($report) => ['label' => $report->product->name, 'data' => collect($metrics)->keys()->map(fn ($field) => round($intensity($report, $field) / $maxima[$field] * 100))])->values()]) }}"></canvas></div>
            </section>
            <section class="panel p-6">
                <h2 class="text-lg font-black">Score environnemental</h2>
                <div class="mt-4 h-80"><canvas aria-label="Scores comparés" data-esg-chart="{{ json_encode(['type' => 'bar', 'labels' => $reports->map(fn ($report) => $report->product->name), 'datasets' => [['label' => 'Score /100', 'data' => $reports->pluck('impact_score')]]]) }}"></canvas></div>
            </section>
        </div>
        <div class="panel mt-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50"><tr>@foreach(['Produit', 'Catégorie', 'Classe', 'Score', 'CO₂ / kg', 'Eau / kg', 'Énergie / kg', 'Déchets / kg'] as $heading)<th class="px-5 py-3 text-left text-[13px] font-bold text-slate-400">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($reports->sortByDesc('impact_score') as $report)
                        <tr>
                            <td class="px-5 py-3 font-bold"><a class="hover:text-sky-700" href="{{ route('admin.impact-reports.show', $report) }}">{{ $report->product->name }}</a></td>
                            <td class="px-5 py-3">{{ $report->product->category }}</td>
                            <td class="px-5 py-3">@include('modules.esg._grade', ['report' => $report, 'size' => 'h-8 w-8 text-sm'])</td>
                            <td class="px-5 py-3 font-black">{{ $report->impact_score }}</td>
                            @foreach(array_keys($metrics) as $field)<td class="px-5 py-3">{{ number_format($intensity($report, $field), 2, ',', ' ') }}</td>@endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-app-layout>

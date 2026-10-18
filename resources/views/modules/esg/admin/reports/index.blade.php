<x-app-layout title="Impact ESG">
    @vite('resources/js/esg-charts.js')
    <x-module.hero module="esg" title="Impact ESG & analyse environnementale" description="CO₂, eau, énergie et déchets calculés pour chaque produit à partir de sa composition, de son cycle de vie et de sa circularité.">
        <x-slot:stats>
            <x-module.stat label="Rapports" :value="$stats['reports']"/>
            <x-module.stat label="Score moyen" :value="$stats['average'].'/100'"/>
            <x-module.stat label="CO₂ cumulé" :value="number_format($stats['carbon'], 0, ',', ' ')" hint="kg CO₂e"/>
            <x-module.stat label="Validés" :value="$stats['validated']"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.esg.admin._tabs')

    <div class="mb-8 grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('admin.impact-reports.store') }}" class="panel space-y-4 p-6">
            @csrf
            <div><p class="text-[13px] font-semibold text-sky-600">Nouveau calcul</p><h2 class="mt-1 text-lg font-black">Générer un rapport produit</h2></div>
            <x-ui.select name="product_id" label="Produit" :options="$products->mapWithKeys(fn ($product) => [$product->id => $product->name.' · '.$product->sku])" required/>
            <div class="grid grid-cols-2 gap-3">
                <x-module.field name="product_weight_kg" type="number" step="0.01" min="0.05" label="Poids (kg)" placeholder="auto"/>
                <x-module.field name="season" label="Saison" placeholder="AH 2026"/>
            </div>
            <x-module.button module="esg" class="w-full">Calculer l’impact</x-module.button>
        </form>
        <section class="panel p-6">
            <p class="text-[13px] font-semibold text-sky-600">Répartition</p>
            <h2 class="mt-1 text-lg font-black">Classes environnementales</h2>
            <div class="mt-3 h-56"><canvas aria-label="Répartition des classes" data-esg-chart="{{ json_encode(['type' => 'doughnut', 'grades' => true, 'labels' => $grades->keys(), 'datasets' => [['label' => 'Produits', 'data' => $grades->values()]]]) }}"></canvas></div>
        </section>
        <section class="panel p-6">
            <p class="text-[13px] font-semibold text-sky-600">Par marque</p>
            <h2 class="mt-1 text-lg font-black">Score moyen</h2>
            <div class="mt-3 h-56"><canvas aria-label="Score moyen par marque" data-esg-chart="{{ json_encode(['type' => 'bar', 'horizontal' => true, 'labels' => $byBrand->keys(), 'datasets' => [['label' => 'Score', 'data' => $byBrand->values()]]]) }}"></canvas></div>
        </section>
    </div>

    <div class="panel overflow-hidden">
        <form class="flex flex-wrap gap-3 border-b border-slate-100 p-4 dark:border-slate-800">
            <select name="brand" class="field !mt-0 max-w-xs" onchange="this.form.submit()"><option value="">Toutes les marques</option>@foreach($brands as $id => $name)<option value="{{ $id }}" @selected((string) request('brand') === (string) $id)>{{ $name }}</option>@endforeach</select>
            <select name="grade" class="field !mt-0 max-w-[10rem]" onchange="this.form.submit()"><option value="">Toutes classes</option>@foreach(['A', 'B', 'C', 'D', 'E'] as $grade)<option @selected(request('grade') === $grade)>{{ $grade }}</option>@endforeach</select>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-slate-50"><tr>@foreach(['Produit', 'Classe', 'Score', 'CO₂ (kg)', 'Eau (L)', 'Statut', ''] as $heading)<th class="px-5 py-3 text-left text-[13px] font-bold text-slate-400">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reports as $report)
                        <tr>
                            <td class="px-5 py-4 text-sm"><p class="font-bold">{{ $report->product->name }}</p><p class="text-xs text-slate-400">{{ $report->brand?->name }} · {{ $report->created_at->format('d/m/Y') }}</p></td>
                            <td class="px-5 py-4">@include('modules.esg._grade', ['report' => $report, 'size' => 'h-8 w-8 text-sm'])</td>
                            <td class="w-40 px-5 py-4"><x-module.bar module="esg" :value="$report->impact_score" :display="$report->impact_score.'/100'" label="Score"/></td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ number_format((float) $report->carbon_kg, 2, ',', ' ') }}</td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ number_format((float) $report->water_l, 0, ',', ' ') }}</td>
                            <td class="px-5 py-4"><x-module.badge :tone="$report->status === 'validated' ? 'good' : 'esg'">{{ $report->status === 'validated' ? 'Validé' : 'Brouillon' }}</x-module.badge></td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('admin.impact-reports.show', $report) }}" class="rounded-lg px-3 py-2 text-sm font-bold text-sky-700 hover:bg-sky-50">Ouvrir</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-ui.empty message="Aucun rapport d’impact calculé."/></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())<div class="border-t border-slate-100 p-4">{{ $reports->links() }}</div>@endif
    </div>
</x-app-layout>

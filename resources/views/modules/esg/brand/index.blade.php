<x-brand-layout>
    @vite('resources/js/esg-charts.js')
    <x-module.hero module="esg" :eyebrow="'Tableau de bord ESG · '.$brand->name" title="Impact environnemental de ma marque" description="Suivez l’empreinte de vos produits et voyez votre score évoluer : plus un produit est circulaire, meilleur est son score.">
        <x-slot:stats>
            <x-module.stat label="Score moyen" :value="$dashboard['averageScore'].'/100'"/>
            <x-module.stat label="CO₂ catalogue" :value="number_format($dashboard['totals']['carbon'], 1, ',', ' ')" hint="kg CO₂e (1 unité / produit)"/>
            <x-module.stat label="Eau" :value="number_format($dashboard['totals']['water'], 0, ',', ' ')" hint="litres"/>
            <x-module.stat label="Rapports validés" :value="$dashboard['validated'].' / '.$dashboard['latest']->count()"/>
        </x-slot:stats>
    </x-module.hero>

    <div class="mb-8 grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('brand.impact.store') }}" class="shadow-[0_4px_0_rgba(15,23,42,.06),0_16px_30px_-20px_rgba(15,23,42,.4)] space-y-4 rounded-3xl border border-sky-100 bg-white p-6">
            @csrf
            <div><p class="text-xs font-black uppercase tracking-wider text-sky-600">Calculer</p><h2 class="mt-1 text-lg font-black">Impact d’un produit</h2></div>
            <x-ui.select name="product_id" label="Produit" :options="$products->pluck('name', 'id')" required/>
            <div class="grid grid-cols-2 gap-3">
                <x-module.field name="product_weight_kg" type="number" step="0.01" min="0.05" label="Poids (kg)" placeholder="auto"/>
                <x-module.field name="season" label="Saison / collection" placeholder="PE 2027"/>
            </div>
            <x-module.button module="esg" class="w-full">Lancer le calcul</x-module.button>
        </form>
        <section class="shadow-[0_4px_0_rgba(15,23,42,.06),0_16px_30px_-20px_rgba(15,23,42,.4)] rounded-3xl border border-slate-200 bg-white p-6">
            <p class="text-xs font-black uppercase tracking-wider text-sky-600">Catalogue</p>
            <h2 class="mt-1 text-lg font-black">Classes environnementales</h2>
            <div class="mt-3 h-52"><canvas aria-label="Classes" data-esg-chart="{{ json_encode(['type' => 'doughnut', 'grades' => true, 'labels' => $dashboard['grades']->keys(), 'datasets' => [['label' => 'Produits', 'data' => $dashboard['grades']->values()]]]) }}"></canvas></div>
        </section>
        <section class="shadow-[0_4px_0_rgba(15,23,42,.06),0_16px_30px_-20px_rgba(15,23,42,.4)] rounded-3xl border border-slate-200 bg-white p-6">
            <p class="text-xs font-black uppercase tracking-wider text-sky-600">Évolution</p>
            <h2 class="mt-1 text-lg font-black">Score moyen par mois</h2>
            <div class="mt-3 h-52"><canvas aria-label="Évolution" data-esg-chart="{{ json_encode(['type' => 'line', 'labels' => $dashboard['trend']->keys(), 'datasets' => [['label' => 'Score', 'data' => $dashboard['trend']->values()]]]) }}"></canvas></div>
        </section>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="shadow-[0_4px_0_rgba(15,23,42,.06),0_16px_30px_-20px_rgba(15,23,42,.4)] rounded-3xl border border-slate-200 bg-white p-6 lg:col-span-2">
            <h2 class="text-lg font-black">Mes produits</h2>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($dashboard['latest'] as $report)
                    <a href="{{ route('brand.impact.show', $report) }}" class="flex items-center gap-4 py-3 transition hover:bg-sky-50/50">
                        @include('modules.esg._grade', ['report' => $report])
                        <div class="min-w-0 flex-1"><p class="truncate font-bold">{{ $report->product->name }}</p><p class="text-xs text-slate-500">{{ number_format((float) $report->carbon_kg, 2, ',', ' ') }} kg CO₂e · {{ number_format((float) $report->water_l, 0, ',', ' ') }} L · {{ $report->status === 'validated' ? 'validé' : 'brouillon' }}</p></div>
                        <x-module.bar class="w-32" module="esg" :value="$report->impact_score" :display="$report->impact_score.'/100'" label="Score"/>
                    </a>
                @empty
                    <x-ui.empty message="Aucun calcul : lancez votre premier rapport d’impact."/>
                @endforelse
            </div>
        </section>
        <section class="shadow-[0_4px_0_rgba(15,23,42,.06),0_16px_30px_-20px_rgba(15,23,42,.4)] rounded-3xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-black">Score par catégorie</h2>
            <div class="mt-4 space-y-3">
                @forelse($dashboard['byCategory'] as $category => $score)
                    <x-module.bar module="esg" :label="$category" :value="$score" :display="$score.'/100'"/>
                @empty
                    <p class="text-sm text-slate-500">—</p>
                @endforelse
            </div>
            <div class="mt-6 rounded-2xl bg-sky-50 p-4 text-sm text-sky-900"><strong>{{ number_format($dashboard['totals']['avoided'], 1, ',', ' ') }} kg</strong> de déchets évités grâce aux retours traités de vos produits.</div>
        </section>
    </div>
</x-brand-layout>

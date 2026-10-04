<x-app-layout title="Centre de pilotage">
    @vite('resources/js/admin-charts.js')

    <section class="relative overflow-hidden rounded-[2rem] bg-slate-950 p-7 text-white shadow-2xl shadow-slate-900/15 sm:p-9">
        <div class="absolute -right-28 -top-36 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-40 w-72 rounded-full bg-sky-500/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-7 xl:flex-row xl:items-end xl:justify-between">
            <div class="animate-fade-up">
                <div class="flex items-center gap-2 text-xs font-black uppercase tracking-[.2em] text-emerald-300"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>Données actualisées</div>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Circularity Intelligence Center</h2>
                <p class="mt-3 max-w-2xl leading-7 text-slate-300">Suivez le catalogue, les collectes, les résultats de traitement et la préparation aux référentiels depuis un seul espace.</p>
            </div>
            <div class="grid shrink-0 grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-2 2xl:grid-cols-4">
                @foreach([['Taux recyclé',$stats['recyclingRate'].'%','text-emerald-300'],['Taux réemploi',$stats['reuseRate'].'%','text-sky-300'],['Poids collecté',$stats['weight'].' kg','text-violet-300'],['Scans traités',$stats['scans'],'text-amber-300']] as $metric)
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $metric[0] }}</p><p class="mt-2 text-xl font-black {{ $metric[2] }}">{{ $metric[1] }}</p></div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Produits',$stats['products'],'Passeports textiles','shirt','from-emerald-500 to-teal-600'],
            ['Matières',$stats['materials'],'Référentiel matière','layers','from-sky-500 to-cyan-600'],
            ['Programmes',$stats['programs'],'Solutions actives','recycle','from-violet-500 to-purple-600'],
            ['Points',$stats['points'],'Partenaires actifs','map-pin','from-amber-500 to-orange-600'],
            ['Retours',$stats['returns'],'Textiles déclarés','refresh','from-rose-500 to-pink-600'],
            ['Poids',$stats['weight'].' kg','Volume collecté','scale','from-lime-500 to-emerald-600'],
            ['Scans',$stats['scans'],'Analyses OCR','scan','from-indigo-500 to-blue-600'],
            ['Évaluations',$stats['assessments'],'Dossiers conformité','check','from-slate-600 to-slate-800']
        ] as $index=>$stat)
            <article class="panel animate-fade-up group relative overflow-hidden p-5 transition duration-300 hover:-translate-y-1 hover:shadow-xl" style="animation-delay: {{ $index * 55 }}ms">
                <div class="absolute -right-7 -top-7 h-24 w-24 rounded-full bg-gradient-to-br {{ $stat[4] }} opacity-[.08] transition group-hover:scale-125 group-hover:opacity-[.14]"></div>
                <div class="flex items-center justify-between"><span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br {{ $stat[4] }} text-white shadow-lg"><x-icon :name="$stat[3]" class="h-5 w-5" /></span></div>
                <p class="mt-5 text-2xl font-black tracking-tight">{{ $stat[1] }}</p><p class="text-sm font-black text-slate-700">{{ $stat[0] }}</p><p class="mt-1 text-xs text-slate-400">{{ $stat[2] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.45fr_.75fr]">
        <article class="panel p-5 sm:p-6"><div class="mb-5 flex items-center justify-between"><div><p class="text-xs font-black uppercase tracking-wider text-emerald-600">Tendance sur 6 mois</p><h3 class="mt-1 text-lg font-black">Retours et poids collecté</h3></div><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">Temps réel</span></div><div class="h-80"><canvas id="returnTrendChart"></canvas></div></article>
        <article class="panel p-5 sm:p-6"><div class="mb-5"><p class="text-xs font-black uppercase tracking-wider text-violet-600">Flux opérationnel</p><h3 class="mt-1 text-lg font-black">État des retours</h3></div><div class="h-80"><canvas id="returnStatusChart"></canvas></div></article>
    </section>

    <section class="mt-6 grid gap-6 lg:grid-cols-2">
        <article class="panel p-5 sm:p-6"><div class="mb-5"><p class="text-xs font-black uppercase tracking-wider text-sky-600">Qualité du catalogue</p><h3 class="mt-1 text-lg font-black">Scores moyens des produits</h3></div><div class="h-72"><canvas id="scoreRadarChart"></canvas></div></article>
        <article class="panel p-5 sm:p-6"><div class="mb-5"><p class="text-xs font-black uppercase tracking-wider text-amber-600">Réseau partenaire</p><h3 class="mt-1 text-lg font-black">Programmes par type</h3></div><div class="h-72"><canvas id="programTypeChart"></canvas></div></article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-3">
        <article class="panel xl:col-span-1"><div class="border-b border-slate-100 p-5"><p class="text-xs font-black uppercase tracking-wider text-emerald-600">Catalogue</p><h3 class="mt-1 font-black">Derniers produits</h3></div><div class="divide-y divide-slate-100">@forelse($recentProducts as $product)<div class="flex items-center gap-3 p-4"><img src="{{ $product->image_url }}" alt="" class="h-10 w-10 rounded-xl object-cover"><div class="min-w-0 flex-1"><p class="truncate text-sm font-black">{{ $product->name }}</p><p class="truncate text-xs text-slate-400">{{ $product->brand }} · {{ $product->sku }}</p></div><x-ui.status :value="$product->status"/></div>@empty<x-ui.empty/>@endforelse</div></article>
        <article class="panel xl:col-span-1"><div class="border-b border-slate-100 p-5"><p class="text-xs font-black uppercase tracking-wider text-sky-600">Collecte</p><h3 class="mt-1 font-black">Derniers retours</h3></div><div class="divide-y divide-slate-100">@forelse($recentReturns as $return)<div class="flex items-center gap-3 p-4"><span class="grid h-10 w-10 place-items-center rounded-xl bg-sky-50 text-sky-700"><x-icon name="refresh" class="h-5 w-5" /></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-black">{{ $return->product->name }}</p><p class="truncate text-xs text-slate-400">{{ $return->reference }} · {{ $return->collectionPoint->city }}</p></div><x-ui.status :value="$return->status"/></div>@empty<x-ui.empty/>@endforelse</div></article>
        <article class="panel xl:col-span-1"><div class="border-b border-slate-100 p-5"><p class="text-xs font-black uppercase tracking-wider text-amber-600">Conformité</p><h3 class="mt-1 font-black">Échéances des preuves</h3></div><div class="divide-y divide-slate-100">@forelse($expiringEvidences as $evidence)<div class="flex items-center gap-3 p-4"><span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-700"><x-icon name="document" class="h-5 w-5" /></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-black">{{ $evidence->title }}</p><p class="truncate text-xs text-slate-400">{{ $evidence->assessment->organization_name }}</p></div><span class="text-xs font-black text-slate-500">{{ $evidence->expires_at->format('d/m/y') }}</span></div>@empty<x-ui.empty/>@endforelse</div></article>
    </section>

    <details class="panel mt-6 p-5">
        <summary class="cursor-pointer text-sm font-semibold">Consulter les données des graphiques</summary>
        <p class="mt-3 text-xs text-slate-500">Données mises à jour au chargement de la page. Les volumes et les poids utilisent deux axes distincts.</p>
        <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><caption class="sr-only">Retours et poids collecté sur six mois</caption><thead><tr><th class="p-3">Mois</th><th class="p-3">Retours</th><th class="p-3">Poids (kg)</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($charts['returnTrend'] as $month)<tr><td class="p-3">{{ $month['label'] }}</td><td class="p-3">{{ $month['count'] }}</td><td class="p-3">{{ $month['weight'] }}</td></tr>@endforeach</tbody></table></div>
        <div class="mt-5 grid gap-5 sm:grid-cols-3">
            @foreach(['statusBreakdown' => 'État des retours', 'programTypes' => 'Programmes par type'] as $key => $label)
                <div><h3 class="mb-2 text-sm font-semibold">{{ $label }}</h3><dl class="space-y-2 text-sm">@foreach($charts[$key] as $item)<div class="flex justify-between gap-3"><dt>{{ $item['label'] }}</dt><dd class="font-semibold tabular-nums">{{ $item['value'] }}</dd></div>@endforeach</dl></div>
            @endforeach
            <div><h3 class="mb-2 text-sm font-semibold">Scores moyens</h3><dl class="space-y-2 text-sm">@foreach($charts['scoreAverages'] as $label => $value)<div class="flex justify-between gap-3"><dt>{{ $label }}</dt><dd class="font-semibold tabular-nums">{{ $value }}/100</dd></div>@endforeach</dl></div>
        </div>
    </details>
    <script id="admin-chart-data" type="application/json">@json($charts)</script>
</x-app-layout>

@php
$layout = $space === 'admin' ? 'app-layout' : 'brand-layout';
$ring = ['rose' => '#e11d48', 'amber' => '#d97706', 'emerald' => '#059669'];
$badge = ['rose' => 'bg-rose-50 text-rose-700 ring-rose-200', 'amber' => 'bg-amber-50 text-amber-800 ring-amber-200', 'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'];
$bar = fn (int $score) => $score >= 60 ? 'bg-rose-500' : ($score >= 30 ? 'bg-amber-500' : 'bg-emerald-500');
$chip = fn (int $days) => $days < 0 ? 'bg-rose-600 text-white' : ($days <= 30 ? 'bg-rose-50 text-rose-700' : ($days <= 90 ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-600'));
@endphp
<x-dynamic-component :component="$layout" title="Risques fournisseurs">
    <x-module.hero module="suppliers" eyebrow="Fournisseurs · pilotage des risques" title="Risques fournisseurs & alertes"
        description="Un score de 0 à 100 par fournisseur, calculé sur ses certificats, ses livraisons, sa conformité et ses retards. Les certificats qui expirent déclenchent une alerte à 30 jours, à 7 jours et le jour J.">
        @if($space === 'brand')
            <x-slot:actions>
                <form method="POST" action="{{ route('brand.suppliers-risk.alerts') }}">@csrf
                    <button class="inline-flex items-center gap-2 rounded-xl bg-fuchsia-400 px-5 py-3 text-sm font-black text-slate-950 hover:bg-fuchsia-300">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                        Envoyer les alertes maintenant
                    </button>
                </form>
            </x-slot:actions>
        @endif
        <x-slot:stats>
            <x-module.stat label="Risque moyen" :value="$stats['average'].'/100'"/>
            <x-module.stat label="Risque élevé" :value="$stats['high']" hint="fournisseur(s)"/>
            <x-module.stat label="Expirent ≤ 30 j" :value="$stats['expiring30']" hint="certificat(s)"/>
            <x-module.stat label="Expirés" :value="$stats['expired']" hint="certificat(s)"/>
        </x-slot:stats>
    </x-module.hero>

    @include($space === 'admin' ? 'modules.suppliers.admin._tabs' : 'modules.suppliers.brand._tabs')

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <section class="panel overflow-hidden">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 p-5">
                <div><p class="text-xs font-black uppercase tracking-wider text-fuchsia-600">Classement</p><h2 class="mt-1 text-lg font-black">Du plus risqué au plus sûr</h2></div>
                <p class="text-xs text-slate-500">Certificats {{ \App\Services\SupplierRiskService::WEIGHTS['certificates'] * 100 }} % · OTIF {{ \App\Services\SupplierRiskService::WEIGHTS['delivery'] * 100 }} % · conformité {{ \App\Services\SupplierRiskService::WEIGHTS['compliance'] * 100 }} % · retards {{ \App\Services\SupplierRiskService::WEIGHTS['orders'] * 100 }} %</p>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($portfolio as $row)
                    @php $supplier = $row['supplier']; @endphp
                    <article class="grid gap-5 p-5 md:grid-cols-[auto_minmax(0,1fr)]" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                        <div class="relative h-20 w-20 shrink-0 rounded-full" style="background: conic-gradient({{ $ring[$row['tone']] }} 0% {{ $row['score'] }}%, #e2e8f0 {{ $row['score'] }}% 100%)" role="img" aria-label="Risque {{ $row['score'] }} sur 100">
                            <div class="absolute inset-[8px] grid place-items-center rounded-full bg-white text-center dark:bg-slate-900">
                                <span><span class="block text-xl font-black tabular-nums">{{ $row['score'] }}</span><span class="block text-[10px] font-semibold text-slate-400">/100</span></span>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ $space === 'admin' ? route('admin.suppliers.show', $supplier) : route('brand.suppliers.show', $supplier) }}" class="text-lg font-black hover:text-fuchsia-700">{{ $supplier->name }}</a>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 {{ $badge[$row['tone']] }}">Risque {{ $row['level'] }}</span>
                                <span class="text-sm text-slate-500">{{ $supplier->country }}@if($space === 'admin') · {{ $supplier->brand?->name }}@endif</span>
                            </div>
                            <dl class="mt-3 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                                @foreach($row['factors'] as $factor)
                                    <div>
                                        <div class="flex justify-between text-xs"><dt class="font-semibold text-slate-600 dark:text-slate-300">{{ $factor['label'] }} <span class="font-normal text-slate-400">· {{ $factor['weight'] }} %</span></dt><dd class="font-bold tabular-nums">{{ $factor['score'] }}</dd></div>
                                        <div class="mt-1 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-1.5 rounded-full {{ $bar($factor['score']) }}" style="width: {{ max(3, $factor['score']) }}%"></div></div>
                                    </div>
                                @endforeach
                            </dl>
                            <button type="button" @click="open = !open" class="mt-3 text-xs font-bold text-fuchsia-700 hover:underline" x-text="open ? 'Masquer le détail' : 'Voir le détail et les actions'"></button>
                            <div x-show="open" x-cloak class="mt-3 space-y-2 rounded-2xl bg-slate-50 p-4 text-sm dark:bg-slate-800/60">
                                @foreach($row['factors'] as $factor)<p><strong>{{ $factor['label'] }} :</strong> {{ $factor['detail'] }}</p>@endforeach
                                <p class="pt-1 font-bold text-fuchsia-800 dark:text-fuchsia-300">À faire</p>
                                <ul class="list-disc space-y-1 pl-5">@foreach($row['actions'] as $action)<li>{{ $action }}</li>@endforeach</ul>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="p-8 text-center text-sm text-slate-500">Aucun fournisseur pour le moment.</p>
                @endforelse
            </div>
        </section>

        <aside class="space-y-6">
            <section class="panel overflow-hidden">
                <div class="border-b border-slate-100 p-5"><p class="text-xs font-black uppercase tracking-wider text-amber-600">Échéancier</p><h2 class="mt-1 font-black">Certificats sur 120 jours</h2></div>
                <ol class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($expiring as $row)
                        <li class="flex items-center gap-3 p-4">
                            <span class="w-20 shrink-0 rounded-lg px-2 py-1.5 text-center text-xs font-black tabular-nums {{ $chip($row['days']) }}">{{ $row['days'] < 0 ? 'Expiré' : ($row['days'] === 0 ? 'Aujourd’hui' : 'J−'.$row['days']) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-bold">{{ $row['certificate']->displayName() }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $row['supplier']->name }} · lot {{ $row['batch']->batch_code }} · {{ $row['date']->format('d/m/Y') }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="p-5 text-sm text-slate-500">Aucun certificat n’expire dans les 120 prochains jours.</li>
                    @endforelse
                </ol>
            </section>

            <section class="panel overflow-hidden">
                <div class="border-b border-slate-100 p-5"><p class="text-xs font-black uppercase tracking-wider text-fuchsia-600">Historique</p><h2 class="mt-1 font-black">Alertes envoyées</h2></div>
                <ul class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                    @forelse($alerts as $alert)
                        <li class="p-4">
                            <p class="font-semibold">{{ $alert->certificate?->displayName() }} <span class="font-normal text-slate-500">· {{ $alert->threshold === 0 ? 'expiré' : 'à '.$alert->threshold.' jours' }}</span></p>
                            <p class="text-xs text-slate-500">{{ $alert->certificate?->batch?->supplier?->name }} · envoyée le {{ $alert->sent_at->format('d/m/Y à H:i') }} à la marque et au fournisseur</p>
                        </li>
                    @empty
                        <li class="p-5 text-slate-500">Aucune alerte envoyée pour le moment. Elles partent chaque matin à 8 h (tâche planifiée).</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>
</x-dynamic-component>

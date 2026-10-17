{{-- Analyse de risque IA d'un fournisseur. Variables : $supplier, $action (route POST) --}}
@php $risk = $supplier->ai_risk; @endphp
<section class="mb-6 overflow-hidden rounded-3xl border border-indigo-200 bg-gradient-to-br from-white to-indigo-50/70 p-6 shadow-sm dark:border-indigo-900 dark:from-slate-900 dark:to-indigo-950/30">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-wider text-indigo-600">Intelligence artificielle</p>
            <h2 class="mt-1 text-lg font-black">Analyse de risque fournisseur</h2>
            <p class="text-sm text-slate-500">Certificats expirés ou inadaptés, lots refusés, écarts de conformité : l’IA détecte les risques et propose un plan d’action.</p>
        </div>
        <form method="POST" action="{{ $action }}">@csrf<x-module.button module="suppliers">✦ {{ $risk ? 'Relancer l’analyse IA' : 'Lancer l’analyse IA' }}</x-module.button></form>
    </div>
    @if($risk)
        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm dark:bg-slate-900">
                <div class="flex items-center justify-between"><x-module.ai-badge :source="$risk['source']"/><span class="text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($risk['analysed_at'])->diffForHumans() }}</span></div>
                <p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">Niveau de risque</p>
                <p class="mt-1 text-3xl font-black {{ ['élevé' => 'text-rose-600', 'moyen' => 'text-amber-600'][$risk['level']] ?? 'text-emerald-600' }}">{{ ucfirst($risk['level']) }}</p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ ['élevé' => 'bg-rose-500', 'moyen' => 'bg-amber-500'][$risk['level']] ?? 'bg-emerald-500' }}" style="width: {{ max(4, $risk['score']) }}%"></div></div>
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $risk['summary'] }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm dark:bg-slate-900">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Constats ({{ count($risk['risks']) }})</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse($risk['risks'] as $item)
                        <li class="flex gap-2"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ ['high' => 'bg-rose-500', 'medium' => 'bg-amber-500'][$item['severity']] ?? 'bg-slate-400' }}"></span>{{ $item['text'] }}</li>
                    @empty
                        <li class="text-emerald-700">Aucun risque détecté.</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm dark:bg-slate-900">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Plan d’action recommandé</p>
                <ol class="mt-3 list-inside list-decimal space-y-2 text-sm text-slate-700 dark:text-slate-200">@foreach($risk['recommendations'] as $recommendation)<li>{{ $recommendation }}</li>@endforeach</ol>
            </div>
        </div>
    @endif
</section>

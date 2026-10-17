{{-- Recommandations IA pour améliorer le score. Variables : $report, $action (route POST) --}}
@php $ai = $report->ai_recommendations; @endphp
<section class="mt-6 overflow-hidden rounded-3xl border border-sky-200 bg-gradient-to-br from-white to-sky-50/70 p-6 shadow-sm dark:border-sky-900 dark:from-slate-900 dark:to-sky-950/30">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-wider text-sky-600">Intelligence artificielle</p>
            <h2 class="mt-1 text-lg font-black">Comment améliorer ce score ?</h2>
            <p class="max-w-2xl text-sm text-slate-500">Le moteur simule chaque action (matière alternative, réparabilité, recyclabilité) et l’IA explique les priorités. Les scores projetés sont calculés, pas inventés.</p>
        </div>
        <form method="POST" action="{{ $action }}">@csrf<x-module.button module="esg">✦ {{ $ai ? 'Actualiser les recommandations' : 'Recommandations IA' }}</x-module.button></form>
    </div>
    @if($ai)
        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm dark:bg-slate-900">
                <x-module.ai-badge :source="$ai['source']"/>
                <div class="mt-4 flex items-center gap-3 text-center">
                    <div class="flex-1 rounded-xl bg-slate-50 p-3 dark:bg-slate-800"><p class="text-xs font-bold uppercase text-slate-400">Actuel</p><p class="text-3xl font-black">{{ $ai['current_score'] }}</p></div>
                    <span class="text-2xl text-sky-500">→</span>
                    <div class="flex-1 rounded-xl bg-sky-600 p-3 text-white"><p class="text-xs font-bold uppercase text-sky-100">Potentiel</p><p class="text-3xl font-black">{{ $ai['best_score'] }}</p></div>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $ai['summary'] }}</p>
            </div>
            <div class="space-y-3 lg:col-span-2">
                @forelse($ai['recommendations'] as $recommendation)
                    <div class="flex gap-4 rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900">
                        <div class="grid h-12 w-14 shrink-0 place-items-center rounded-xl bg-sky-50 text-center dark:bg-sky-950"><span class="text-lg font-black text-sky-700">+{{ $recommendation['gain'] }}</span></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2"><p class="font-black">{{ $recommendation['title'] }}</p><x-module.badge tone="esg">{{ $recommendation['type'] === 'material' ? 'Matière' : 'Circularité' }} · {{ $recommendation['projected_score'] }}/100</x-module.badge></div>
                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $recommendation['detail'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-white p-5 text-sm text-emerald-700 shadow-sm dark:bg-slate-900">Produit déjà optimisé : aucune action simple n’améliore son score.</div>
                @endforelse
            </div>
        </div>
    @endif
</section>

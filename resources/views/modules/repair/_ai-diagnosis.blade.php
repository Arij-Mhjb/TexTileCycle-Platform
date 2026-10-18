{{-- Résultat du diagnostic IA d'une réparation : $order --}}
@php $diagnosis = $order->ai_diagnosis; @endphp
@if($diagnosis)
    <div class="rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-amber-50 p-4 text-sm dark:border-violet-900 dark:from-violet-950/30 dark:to-amber-950/20">
        <div class="flex flex-wrap items-center gap-2">
            <x-module.ai-badge :source="$diagnosis['source']"/>
            <span class="text-xs font-bold text-violet-800 dark:text-violet-300">Confiance {{ $diagnosis['confidence'] }} %</span>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div class="rounded-xl bg-white/80 p-2 text-center dark:bg-slate-900"><p class="text-[10px] font-bold text-slate-400">Décision</p><p class="font-black text-amber-800 dark:text-amber-300">{{ \App\Models\RepairOrder::DECISIONS[$diagnosis['decision']] ?? $diagnosis['decision'] }}</p></div>
            <div class="rounded-xl bg-white/80 p-2 text-center dark:bg-slate-900"><p class="text-[10px] font-bold text-slate-400">Grade visé</p><p class="font-black">{{ $diagnosis['grade'] }}</p></div>
            <div class="rounded-xl bg-white/80 p-2 text-center dark:bg-slate-900"><p class="text-[10px] font-bold text-slate-400">Coût estimé</p><p class="font-black">{{ number_format($diagnosis['estimated_cost'], 0, ',', ' ') }} TND</p></div>
            <div class="rounded-xl bg-white/80 p-2 text-center dark:bg-slate-900"><p class="text-[10px] font-bold text-slate-400">Prix de revente</p><p class="font-black">{{ number_format($diagnosis['resale_price'], 0, ',', ' ') }} TND</p></div>
        </div>
        <p class="mt-3 text-slate-700 dark:text-slate-200">{{ $diagnosis['explanation'] }}</p>
        <p class="mt-2 text-xs text-slate-500">Défauts : {{ implode(' · ', $diagnosis['damages']) }}</p>
    </div>
@endif

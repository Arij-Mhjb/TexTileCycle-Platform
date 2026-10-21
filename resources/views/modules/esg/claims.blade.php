@php
$tone = [
    'misleading' => 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950/40 dark:text-rose-200 dark:ring-rose-900',
    'vague' => 'bg-amber-50 text-amber-900 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-900',
    'unproven' => 'bg-yellow-50 text-yellow-900 ring-yellow-200 dark:bg-yellow-950/30 dark:text-yellow-200 dark:ring-yellow-900',
    'specific' => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-900',
    'none' => 'bg-slate-50 text-slate-600 ring-slate-200 dark:bg-slate-800/60 dark:text-slate-300 dark:ring-slate-700',
];
$risk = fn (int $score) => $score >= 60 ? ['Risque élevé', 'bg-rose-600'] : ($score >= 30 ? ['Risque moyen', 'bg-amber-500'] : ['Risque faible', 'bg-emerald-600']);
$flagged = $products->filter(fn ($row) => $row['analysis']['score'] >= 30)->count();
@endphp
<x-brand-layout title="Anti-greenwashing">
    <x-module.hero module="esg" :eyebrow="'Impact ESG · '.$brand->name" title="Anti-greenwashing"
        description="Depuis le 27 septembre 2026, la directive européenne 2024/825 interdit les allégations écologiques génériques (« éco-responsable », « durable »…) sans preuve et la neutralité carbone par compensation. L’IA vérifie vos textes phrase par phrase.">
        <x-slot:stats>
            <x-module.stat label="Produits analysés" :value="$products->count()"/>
            <x-module.stat label="À corriger" :value="$flagged"/>
            <x-module.stat label="Modèles" value="ClimateBERT" hint="+ mDeBERTa (FR)"/>
            <x-module.stat label="Réglementation" value="UE 2024/825"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.esg.brand._tabs')

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px]">
        {{-- Tester un texte --}}
        <section class="panel p-6">
            <p class="text-xs font-black uppercase tracking-wider text-sky-600">Vérifier un texte</p>
            <h2 class="mt-1 text-lg font-black">Fiche produit, post, étiquette…</h2>
            <form method="POST" action="{{ route('brand.green-claims.analyse') }}" class="mt-4" x-data="{ sending: false }" @submit="sending = true">
                @csrf
                <textarea name="texte" rows="4" class="field" placeholder="Ex. Notre nouvelle veste éco-responsable est neutre en carbone et fabriquée en coton recyclé." required>{{ $text }}</textarea>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <x-module.button module="esg" x-bind:disabled="sending"><span x-text="sending ? 'Analyse par l’IA…' : 'Analyser'">Analyser</span></x-module.button>
                    @foreach(['Une collection éco-responsable et neutre en carbone.', 'T-shirt en 60 % coton recyclé certifié GRS.', 'Our eco-friendly jeans are better for the planet.'] as $example)
                        <button type="button" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-sky-300 dark:border-slate-700 dark:text-slate-300" @click="$root.querySelector('textarea').value = @js($example)">{{ \Illuminate\Support\Str::limit($example, 34) }}</button>
                    @endforeach
                </div>
            </form>

            @if($analysis)
                @php [$riskLabel, $riskColor] = $risk($analysis['score']); @endphp
                <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                    <span class="rounded-xl px-3 py-1.5 text-sm font-black text-white {{ $riskColor }}">{{ $riskLabel }} · {{ $analysis['score'] }}/100</span>
                    <x-module.ai-badge :source="$analysis['source']"/>
                    @if($analysis['models'])<span class="text-xs text-slate-500">{{ implode(', ', $analysis['models']) }}</span>@endif
                </div>
                <ol class="mt-4 space-y-3">
                    @foreach($analysis['sentences'] as $row)
                        <li class="rounded-2xl p-4 ring-1 {{ $tone[$row['verdict']] }}">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="font-semibold">« {{ $row['text'] }} »</p>
                                <span class="shrink-0 rounded-full bg-white/70 px-2.5 py-0.5 text-xs font-black dark:bg-black/20">{{ $row['label'] }}</span>
                            </div>
                            <p class="mt-1 text-sm">{{ $row['why'] }}</p>
                            @if($row['fix'])<p class="mt-2 text-sm"><strong>Correction :</strong> {{ $row['fix'] }}</p>@endif
                            @if($row['ai'])<p class="mt-2 text-xs opacity-75">IA : allégation {{ $row['ai']['claim'] }} %@if($row['ai']['specific'] !== null) · précision {{ $row['ai']['specific'] }} %@endif</p>@endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        {{-- Grille de lecture --}}
        <aside class="panel h-fit p-6">
            <p class="text-xs font-black uppercase tracking-wider text-sky-600">Grille de lecture</p>
            <h2 class="mt-1 font-black">Ce que l’IA signale</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach(\App\Services\Ai\GreenClaimsService::VERDICTS as $verdict => [$label, $help])
                    <li class="flex gap-3"><span class="mt-0.5 h-fit w-24 shrink-0 rounded-full px-2.5 py-0.5 text-center text-xs font-black ring-1 {{ $tone[$verdict] }}">{{ $label }}</span><span class="text-slate-600 dark:text-slate-300">{{ $help }}</span></li>
                @endforeach
            </ul>
            <p class="mt-5 text-xs leading-5 text-slate-500">Phrases en anglais : ClimateBERT (allégation environnementale + précision). En français : modèle multilingue zero-shot. Les règles de la directive s’appliquent dans tous les cas, même sans connexion à Hugging Face.</p>
        </aside>
    </div>

    {{-- Descriptions produits --}}
    <section class="panel mt-6 overflow-hidden">
        <div class="border-b border-slate-100 p-5 dark:border-slate-800"><p class="text-xs font-black uppercase tracking-wider text-sky-600">Catalogue</p><h2 class="mt-1 text-lg font-black">Descriptions de mes produits</h2></div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($products as ['product' => $product, 'analysis' => $productAnalysis])
                @php [$riskLabel, $riskColor] = $risk($productAnalysis['score']); @endphp
                <article class="grid gap-4 p-5 md:grid-cols-[220px_minmax(0,1fr)]">
                    <div>
                        <p class="font-black">{{ $product->name }}</p>
                        <p class="text-xs text-slate-500">{{ $product->category }}</p>
                        <span class="mt-2 inline-block rounded-lg px-2.5 py-1 text-xs font-black text-white {{ $riskColor }}">{{ $riskLabel }} · {{ $productAnalysis['score'] }}</span>
                    </div>
                    <div class="space-y-2">
                        @foreach($productAnalysis['sentences'] as $row)
                            <div class="rounded-xl px-4 py-3 text-sm ring-1 {{ $tone[$row['verdict']] }}">
                                <p><span class="mr-2 rounded-full bg-white/70 px-2 py-0.5 text-[11px] font-black dark:bg-black/20">{{ $row['label'] }}</span>{{ $row['text'] }}</p>
                                @if($row['fix'])<p class="mt-1 text-xs"><strong>Correction :</strong> {{ $row['fix'] }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <p class="p-8 text-center text-sm text-slate-500">Aucun produit avec description.</p>
            @endforelse
        </div>
    </section>
</x-brand-layout>

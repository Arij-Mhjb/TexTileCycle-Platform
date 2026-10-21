@php
$pct = fn ($value) => $value === null ? '—' : (int) round($value).' %';
$gradeTone = ['A' => 'good', 'B' => 'suppliers', 'C' => 'warn', 'D' => 'bad'];
@endphp
<x-dynamic-component :component="$space === 'brand' ? 'brand-layout' : 'app-layout'" title="Performance fournisseurs">
    <x-module.hero module="suppliers" eyebrow="Achats" title="Performance des fournisseurs"
        description="Qui livre à l’heure, en totalité, répond vite et prouve l’origine de sa matière ? Calculé à partir des vraies commandes et livraisons.">
        <x-slot:stats>
            <x-module.stat label="OTIF moyen" :value="$pct($stats['otif'])" hint="à l’heure et complet"/>
            <x-module.stat label="Taux de service" :value="$pct($stats['fill'])"/>
            <x-module.stat label="Commandes en retard" :value="$stats['late']"/>
            <x-module.stat label="Meilleur fournisseur" :value="\Illuminate\Support\Str::limit($stats['best'], 18)"/>
        </x-slot:stats>
    </x-module.hero>

    @include($space === 'brand' ? 'modules.suppliers.brand._tabs' : 'modules.suppliers.admin._tabs')

    <details class="mb-6 rounded-2xl border border-purple-100 bg-purple-50/50 p-5 text-sm dark:border-purple-900 dark:bg-purple-950/20">
        <summary class="cursor-pointer font-bold text-purple-900 dark:text-purple-200">Comment la note est calculée ?</summary>
        <div class="mt-3 grid gap-3 text-slate-600 dark:text-slate-300 md:grid-cols-2">
            <p><strong>OTIF</strong> (On Time In Full) : part des commandes livrées à la date promise <em>et</em> en totalité (tolérance −2 %). Une commande dont la date est dépassée sans livraison complète compte comme ratée.</p>
            <p><strong>Taux de service</strong> : kg reçus / kg commandés. <strong>Réactivité</strong> : délai moyen de réponse à une commande (24 h ou moins = 100, 5 jours ou plus = 0).</p>
            <p><strong>Conformité</strong> : lots vérifiés et certificats valides (score de traçabilité).</p>
            <p><strong>Note /100</strong> = 40 % OTIF + 20 % taux de service + 20 % conformité + 20 % réactivité. Classe A ≥ 85, B ≥ 70, C ≥ 50, sinon D.</p>
        </div>
    </details>

    <div class="space-y-4">
        @forelse($cards as $index => $card)
            @php $supplier = $card['supplier']; @endphp
            <article class="shadow-[0_1px_2px_rgba(15,23,42,.05)] rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-sm font-black text-slate-500 dark:bg-slate-800">#{{ $index + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ $space === 'brand' ? route('brand.suppliers.show', $supplier) : route('admin.suppliers.show', $supplier) }}" class="text-lg font-black hover:text-purple-700">{{ $supplier->name }}</a>
                        <p class="text-sm text-slate-500">{{ $supplier->country }}@if($space === 'admin') · {{ $supplier->brand?->name }}@endif · {{ $card['orders'] }} commande(s), {{ $card['evaluated'] }} évaluée(s)@if($card['open_late']) · <span class="font-semibold text-rose-600">{{ $card['open_late'] }} en retard</span>@endif</p>
                    </div>
                    @if($card['score'] !== null)
                        <div class="text-right">
                            <p class="text-3xl font-black tabular-nums">{{ $card['score'] }}<span class="text-sm text-slate-400">/100</span></p>
                            <x-module.badge :tone="$gradeTone[$card['grade']]">Classe {{ $card['grade'] }}</x-module.badge>
                        </div>
                    @else
                        <x-module.badge>Pas encore de livraison à évaluer</x-module.badge>
                    @endif
                </div>
                <div class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-module.bar module="suppliers" label="OTIF" :value="$card['otif'] ?? 0" :display="$pct($card['otif'])"/>
                    <x-module.bar module="suppliers" label="Taux de service" :value="$card['fill_rate'] ?? 0" :display="$pct($card['fill_rate'])"/>
                    <x-module.bar module="suppliers" label="Conformité" :value="$card['compliance'] ?? 0" :display="$pct($card['compliance'])"/>
                    <x-module.bar module="suppliers" label="Réactivité" :value="$card['responsiveness'] ?? 0"
                        :display="$card['avg_reply_hours'] === null ? '—' : $card['avg_reply_hours'].' h en moyenne'"/>
                </div>
                @if($card['evaluated'])
                    <p class="mt-4 text-xs text-slate-500">À l’heure : {{ $pct($card['on_time']) }} · Complet : {{ $pct($card['in_full']) }} · Retard moyen quand en retard : {{ $card['avg_delay_days'] }} jour(s)</p>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">Aucun fournisseur pour le moment.</div>
        @endforelse
    </div>
</x-dynamic-component>

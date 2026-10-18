<x-app-layout title="Conformité des lots">
    <x-module.hero module="suppliers" title="Conformité produits ↔ lots" description="L’admin vérifie que la matière fournie correspond aux déclarations du produit : matière, origine, certificat valide et lot vérifié.">
        <x-slot:stats>
            <x-module.stat label="Liens" :value="$counts->sum()"/>
            <x-module.stat label="Conformes" :value="$counts['conform'] ?? 0"/>
            <x-module.stat label="Écarts" :value="$counts['mismatch'] ?? 0"/>
            <x-module.stat label="Non contrôlés" :value="$counts['unchecked'] ?? 0"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.suppliers.admin._tabs')

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach(['' => 'Tous', 'conform' => 'Conformes', 'mismatch' => 'Écarts', 'unchecked' => 'Non contrôlés'] as $value => $label)
            <a href="{{ route('admin.traceability.index', array_filter(['conformity' => $value])) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ request('conformity', '') === $value ? 'bg-purple-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-purple-50' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @forelse($links as $link)
            <article class="panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wider text-slate-400">{{ $link->product->brandProfile?->name ?? $link->product->brand }}</p>
                        <h2 class="mt-1 font-black">{{ $link->product->name }}</h2>
                        <p class="text-sm text-slate-500">Lot {{ $link->batch->batch_code }} · {{ $link->batch->material?->name }} · {{ $link->batch->supplier->name }} ({{ $link->batch->origin_country }})</p>
                    </div>
                    <x-module.badge :tone="['conform' => 'good', 'mismatch' => 'warn'][$link->conformity_status] ?? 'neutral'">{{ $link->conformityLabel() }}</x-module.badge>
                </div>
                @if($link->conformity_note)<p class="mt-3 whitespace-pre-line rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-600 dark:bg-slate-800/50">{{ $link->conformity_note }}</p>@endif
                <div class="mt-3 flex items-center justify-between gap-2">
                    <p class="text-xs text-slate-400">{{ $link->checked_at ? 'Contrôlé '.$link->checked_at->diffForHumans() : 'Jamais contrôlé' }}</p>
                    <form method="POST" action="{{ route('admin.traceability.check', $link) }}">@csrf<x-module.button module="suppliers" variant="soft" class="!py-1.5 text-xs">Vérifier la conformité</x-module.button></form>
                </div>
            </article>
        @empty
            <div class="panel lg:col-span-2"><x-ui.empty message="Aucun produit relié à un lot fournisseur."/></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $links->links() }}</div>
</x-app-layout>

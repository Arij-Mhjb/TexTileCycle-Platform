<x-role-layout title="Retours textiles">
    <x-ui.page-heading title="Mes retours textiles" description="Suivez chaque textile depuis la déclaration jusqu’au traitement vérifié." :action="route('product-returns.create')" action-label="Déclarer un retour"/>

    <div class="panel overflow-hidden">
        <div class="border-b border-slate-100 p-4 dark:border-slate-800">
            <form class="grid gap-3 md:grid-cols-[1fr_220px_auto]">
                <input name="search" value="{{ request('search') }}" placeholder="Référence ou produit…" class="field !mt-0">
                <select name="status" class="field !mt-0">
                    <option value="">Tous les statuts</option>
                    @foreach (['declared'=>'Déclaré','dropoff_expected'=>'Dépôt attendu','deposit_declared'=>'Dépôt déclaré','received'=>'Reçu','inspected'=>'Contrôlé','routed'=>'Orienté','completed'=>'Terminé','rejected'=>'Refusé','cancelled'=>'Annulé'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-slate-900 px-5 py-2 font-black text-white">Filtrer</button>
            </form>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($items as $item)
                <article class="grid gap-4 p-5 lg:grid-cols-[1.2fr_1fr_.6fr_.7fr_auto] lg:items-center">
                    <div>
                        <p class="font-black">{{ $item->product->name }}</p>
                        <p class="mt-1 font-mono text-xs text-slate-400">{{ $item->reference }}</p>
                    </div>
                    <div class="text-sm">
                        <p class="font-bold">{{ $item->collectionPoint->name }}</p>
                        <p class="text-xs text-slate-400">{{ $item->collectionPoint->city }}</p>
                    </div>
                    <div>
                        <p class="font-black">{{ $item->confirmed_weight_kg ?? $item->weight_kg ?? '—' }}{{ ($item->confirmed_weight_kg ?? $item->weight_kg) !== null ? ' kg' : '' }}</p>
                        <p class="text-xs text-slate-400">Poids</p>
                    </div>
                    <div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $item->statusLabel() }}</span>
                        <p class="mt-2 text-xs text-slate-400">{{ $item->rewardTransaction?->status === 'validated' ? ($item->rewardTransaction->points.' pts validés') : (($item->rewardTransaction?->points ?? 0).' pts en attente') }}</p>
                    </div>
                    <div class="flex gap-2 lg:justify-end">
                        <a href="{{ route('product-returns.show', $item) }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black text-white">Suivre</a>
                        @if ($item->canConsumerEdit() || auth()->user()->isAdmin())
                            <a href="{{ route('product-returns.edit', $item) }}" class="rounded-xl border px-3 py-2 text-sm font-black">Modifier</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="p-8"><x-ui.empty message="Aucun retour textile pour le moment."/></div>
            @endforelse
        </div>

        @if ($items->hasPages())
            <div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>
        @endif
    </div>
</x-role-layout>


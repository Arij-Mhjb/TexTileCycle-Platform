<x-app-layout title="Récompenses">
    <x-ui.page-heading title="Catalogue de récompenses" description="Créer les avantages que les consommateurs peuvent obtenir avec leurs points." />

    <form method="POST" action="{{ route('admin.reward-items.store') }}" class="panel mt-6 grid gap-4 p-6 md:grid-cols-3">
        @csrf
        <x-ui.input name="name" label="Récompense" required />
        <x-ui.input name="partner_name" label="Partenaire" />
        <x-ui.input name="points_cost" type="number" min="1" label="Coût en points" required />
        <x-ui.input name="stock" type="number" min="0" label="Stock (vide = illimité)" />
        <x-ui.input name="available_until" type="date" label="Disponible jusqu’au" />
        <x-ui.checkbox name="is_active" label="Active" :checked="true" />
        <div class="md:col-span-3">
            <x-ui.textarea name="description" label="Description" />
            <button class="btn-primary mt-3">Ajouter</button>
        </div>
    </form>

    <section class="panel mt-6 p-6">
        <h2 class="text-lg font-black">Valider un code consommateur</h2>
        <p class="mt-1 text-sm text-slate-500">Saisissez le code présenté lors de l’utilisation de la récompense.</p>
        <form method="POST" action="{{ route('admin.reward-redemptions.consume') }}" class="mt-4 flex max-w-xl items-end gap-3">
            @csrf
            <div class="flex-1"><x-ui.input name="code" label="Code de récompense" required /></div>
            <button class="btn-primary">Valider le code</button>
        </form>
    </section>

    <div class="mt-6 space-y-3">
        @forelse($items as $item)
            <article class="panel p-5">
                <form method="POST" action="{{ route('admin.reward-items.update', $item) }}" class="grid gap-3 md:grid-cols-6">
                    @csrf
                    @method('PATCH')
                    <x-ui.input name="name" label="Nom" :value="$item->name" required />
                    <x-ui.input name="partner_name" label="Partenaire" :value="$item->partner_name" />
                    <x-ui.input name="points_cost" type="number" min="1" label="Points" :value="$item->points_cost" required />
                    <x-ui.input name="stock" type="number" min="0" label="Stock" :value="$item->stock" />
                    <x-ui.input name="available_until" type="date" label="Fin" :value="optional($item->available_until)->format('Y-m-d')" />
                    <div>
                        <x-ui.checkbox name="is_active" label="Active" :checked="$item->is_active" />
                        <p class="mt-2 text-xs text-slate-400">{{ $item->redemptions_count }} échange(s)</p>
                    </div>
                    <div class="md:col-span-6">
                        <button class="btn-primary">Enregistrer</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.reward-items.destroy', $item) }}" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <button class="btn-danger">Supprimer/désactiver</button>
                </form>
            </article>
        @empty
            <div class="panel p-6 text-sm text-slate-500">Aucune récompense dans le catalogue.</div>
        @endforelse
    </div>

    <section class="panel mt-6 overflow-hidden">
        <div class="border-b p-5"><h2 class="font-black">Derniers échanges</h2></div>
        <div class="divide-y">
            @forelse($recentRedemptions as $redemption)
                <div class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                    <div><strong>{{ $redemption->item->name }}</strong><p class="text-slate-500">{{ $redemption->user->name }} · {{ $redemption->reference }}</p></div>
                    <div class="text-right"><code class="font-black">{{ $redemption->code }}</code><p class="text-xs text-slate-500">{{ $redemption->status === 'used' ? 'Utilisée' : 'Émise' }}</p></div>
                </div>
            @empty
                <x-ui.empty message="Aucun échange." />
            @endforelse
        </div>
    </section>
</x-app-layout>

<x-brand-layout>
    <x-module.hero module="suppliers" title="Mes fournisseurs" description="Qui a fourni quelle matière, d’où, quand et avec quelle certification : la base de la crédibilité de vos labels.">
        <x-slot:actions>
            <x-module.button module="suppliers" variant="light" :href="route('brand.suppliers.create')">+ Nouveau fournisseur</x-module.button>
        </x-slot:actions>
        <x-slot:stats>
            <x-module.stat label="Fournisseurs" :value="$stats['suppliers']"/>
            <x-module.stat label="Lots déclarés" :value="$stats['batches']"/>
            <x-module.stat label="Lots vérifiés" :value="$stats['verified']"/>
            <x-module.stat label="Conformité moyenne" :value="$stats['score'].' %'"/>
        </x-slot:stats>
    </x-module.hero>

    @include('modules.suppliers.brand._tabs')

    <form class="mb-5 max-w-md"><input name="search" value="{{ request('search') }}" placeholder="Rechercher un fournisseur ou un pays…" class="field !mt-0"></form>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($suppliers as $supplier)
            <a href="{{ route('brand.suppliers.show', $supplier) }}" class="shadow-[0_1px_2px_rgba(15,23,42,.05)] group rounded-2xl border border-slate-200 bg-white p-6 transition hover:-translate-y-1 hover:border-purple-200 hover:shadow-xl hover:shadow-purple-900/5">
                <div class="flex items-start justify-between gap-3">
                    <div class="grid h-12 w-12 place-items-center rounded-2xl bg-purple-50 text-lg font-black text-purple-700">{{ strtoupper(mb_substr($supplier->name, 0, 1)) }}</div>
                    <x-module.badge :tone="$supplier->status === 'active' ? 'good' : ($supplier->status === 'suspended' ? 'bad' : 'warn')">{{ $supplier->statusLabel() }}</x-module.badge>
                </div>
                <h2 class="mt-4 text-lg font-black text-slate-900 group-hover:text-purple-700">{{ $supplier->name }}</h2>
                <p class="text-sm text-slate-500">{{ collect([$supplier->city, $supplier->country])->filter()->join(', ') }}</p>
                @if($supplier->certifications)<p class="mt-3 text-xs font-bold text-purple-600">{{ $supplier->certifications }}</p>@endif
                <div class="mt-5 grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="rounded-xl bg-slate-50 p-2"><p class="text-lg font-black text-slate-900">{{ $supplier->batches_count }}</p>lots</div>
                    <div class="rounded-xl bg-slate-50 p-2"><p class="text-lg font-black text-slate-900">{{ $supplier->verified_batches_count }}</p>vérifiés</div>
                    <div class="rounded-xl bg-slate-50 p-2"><p class="text-lg font-black text-slate-900">{{ $supplier->purchase_orders_count }}</p>commandes</div>
                </div>
                <x-module.bar class="mt-5" module="suppliers" label="Score de conformité" :value="$supplier->compliance_score ?? 0" :display="$supplier->compliance_score !== null ? $supplier->compliance_score.' %' : 'Non évalué'"/>
            </a>
        @empty
            <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-purple-200 bg-white p-10 text-center">
                <p class="text-lg font-black">Aucun fournisseur pour l’instant</p>
                <p class="mt-2 text-sm text-slate-500">Ajoutez vos fournisseurs de matières pour relier chaque produit à des lots certifiés.</p>
                <x-module.button class="mt-5" module="suppliers" :href="route('brand.suppliers.create')">Ajouter mon premier fournisseur</x-module.button>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $suppliers->links() }}</div>
</x-brand-layout>

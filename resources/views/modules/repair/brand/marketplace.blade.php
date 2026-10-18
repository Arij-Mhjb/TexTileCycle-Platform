<x-brand-layout>
    <x-module.hero module="repair" :eyebrow="'Seconde vie · '.$brand->name" title="Marketplace circulaire" description="Vos produits reconditionnés (issus des retours ou de vos invendus réparés) et les offres reçues.">
        <x-slot:stats>
            <x-module.stat label="En vente" :value="$listings->where('status', 'published')->count()"/>
            <x-module.stat label="Offres en attente" :value="$listings->flatMap->offers->where('status', 'pending')->count()"/>
            <x-module.stat label="Vendus" :value="$listings->where('status', 'sold')->count()"/>
            <x-module.stat label="Issus de retours" :value="$listings->whereNotNull('repair_order_id')->count()"/>
        </x-slot:stats>
    </x-module.hero>

    <form method="POST" action="{{ route('brand.marketplace.store') }}" class="shadow-[0_1px_2px_rgba(15,23,42,.05)] mb-8 grid gap-4 rounded-2xl border border-amber-100 bg-white p-6 md:grid-cols-4">
        @csrf
        <div class="md:col-span-4"><p class="text-[13px] font-semibold text-amber-600">Publier</p><h2 class="mt-1 text-lg font-black">Mettre en vente un article reconditionné</h2></div>
        <x-ui.select name="product_id" label="Produit" :options="$products->pluck('name', 'id')" required/>
        <div class="md:col-span-2"><x-module.field name="title" label="Titre" required placeholder="Veste reconditionnée — taille M"/></div>
        <x-module.field name="price" type="number" step="0.01" min="1" label="Prix (TND)" required/>
        <x-ui.select name="condition_grade" label="Grade" :options="\App\Models\MarketplaceListing::GRADES" value="A" required/>
        <div class="md:col-span-3"><x-module.field name="description" label="Description"/></div>
        <div class="md:col-span-4 flex justify-end"><x-module.button module="repair">Publier l’annonce</x-module.button></div>
    </form>

    <div class="space-y-5">
        @forelse($listings as $listing)
            <article class="shadow-[0_1px_2px_rgba(15,23,42,.05)] rounded-2xl border border-slate-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><p class="text-[13px] font-semibold text-amber-600">{{ $listing->gradeLabel() }} · {{ $listing->repair_order_id ? 'issu d’un retour '.$listing->repairOrder?->reference : 'stock marque' }}</p><h2 class="mt-1 text-lg font-black">{{ $listing->title }}</h2><p class="text-sm text-slate-500">{{ $listing->product?->name }}</p></div>
                    <div class="text-right"><p class="text-xl font-black text-amber-700">{{ number_format((float) $listing->price, 2, ',', ' ') }} {{ $listing->currency }}</p><x-module.badge :tone="['sold' => 'good', 'reserved' => 'warn'][$listing->status] ?? 'repair'">{{ $listing->statusLabel() }}</x-module.badge></div>
                </div>
                <div class="mt-4">@include('modules.repair._offers', ['respondRoute' => 'brand.marketplace.offers.respond', 'soldRoute' => 'brand.marketplace.sold'])</div>
            </article>
        @empty
            <x-ui.empty message="Aucune annonce pour vos produits."/>
        @endforelse
    </div>
</x-brand-layout>

<x-app-layout title="Marketplace circulaire">
    <x-module.hero module="repair" title="Marketplace circulaire" description="Articles reconditionnés publiés après réparation, ou mis en vente par les marques.">
        <x-slot:stats>
            <x-module.stat label="En vente" :value="$stats['published']"/>
            <x-module.stat label="Réservés" :value="$stats['reserved']"/>
            <x-module.stat label="Vendus" :value="$stats['sold']"/>
            <x-module.stat label="Valeur revendue" :value="number_format($stats['revenue'], 0, ',', ' ')" hint="TND"/>
        </x-slot:stats>
    </x-module.hero>
    @include('modules.repair.admin._tabs')
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach(['' => 'Toutes', 'published' => 'En vente', 'reserved' => 'Réservées', 'sold' => 'Vendues', 'archived' => 'Archivées'] as $value => $label)
            <a href="{{ route('admin.marketplace.index', array_filter(['status' => $value])) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ request('status', '') === $value ? 'bg-amber-600 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-amber-50' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($listings as $listing)
            <a href="{{ route('admin.marketplace.show', $listing) }}" class="panel group overflow-hidden transition hover:-translate-y-1 hover:shadow-xl">
                <div class="relative h-40 overflow-hidden bg-amber-50"><img src="{{ $listing->product?->image_url ?? asset('images/story/clothes-sorting.jpg') }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105"><span class="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-black text-amber-800">Grade {{ $listing->condition_grade }}</span></div>
                <div class="p-5">
                    <div class="flex items-start justify-between gap-2"><h2 class="font-black">{{ $listing->title }}</h2><p class="whitespace-nowrap font-black text-amber-700">{{ number_format((float) $listing->price, 2, ',', ' ') }} {{ $listing->currency }}</p></div>
                    <p class="mt-1 text-xs text-slate-500">{{ $listing->brand?->name ?? 'TexTileCycle' }} · {{ $listing->repair_order_id ? 'issu d’un retour' : 'stock marque' }}</p>
                    <div class="mt-3 flex items-center gap-2"><x-module.badge :tone="['sold' => 'good', 'reserved' => 'warn', 'archived' => 'neutral'][$listing->status] ?? 'repair'">{{ $listing->statusLabel() }}</x-module.badge>@if($listing->pending_offers_count)<x-module.badge tone="warn">{{ $listing->pending_offers_count }} offre(s) en attente</x-module.badge>@endif</div>
                </div>
            </a>
        @empty
            <div class="panel md:col-span-2 xl:col-span-3"><x-ui.empty message="Aucune annonce."/></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $listings->links() }}</div>
</x-app-layout>

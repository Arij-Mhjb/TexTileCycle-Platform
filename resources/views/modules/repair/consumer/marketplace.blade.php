<x-consumer-layout title="Marketplace circulaire">
    <x-module.hero module="repair" eyebrow="Seconde vie" title="Des vêtements réparés, prêts pour une nouvelle histoire" description="Chaque article est contrôlé, reconditionné par un atelier partenaire et garde son passeport textile."/>
    @include('modules.repair.consumer._tabs')

    <form class="mb-6 flex flex-wrap gap-3">
        <input name="search" value="{{ request('search') }}" placeholder="Rechercher un article…" class="field !mt-0 max-w-xs">
        <select name="grade" class="field !mt-0 max-w-[12rem]" onchange="this.form.submit()"><option value="">Tous les états</option>@foreach(\App\Models\MarketplaceListing::GRADES as $grade => $label)<option value="{{ $grade }}" @selected(request('grade') === $grade)>{{ $grade }} · {{ $label }}</option>@endforeach</select>
    </form>

    <div class="grid gap-6 lg:grid-cols-4">
        <div class="grid gap-5 sm:grid-cols-2 lg:col-span-3 xl:grid-cols-3">
            @forelse($listings as $listing)
                <a href="{{ route('consumer.marketplace.show', $listing) }}" class="consumer-card group overflow-hidden">
                    <div class="relative h-48 overflow-hidden bg-amber-50"><img src="{{ $listing->product?->image_url ?? asset('images/story/clothes-sorting.jpg') }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-105"><span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-xs font-black text-amber-800">{{ $listing->gradeLabel() }}</span></div>
                    <div class="p-5">
                        <p class="text-xs font-bold text-slate-400">{{ $listing->brand?->name ?? 'TexTileCycle' }}</p>
                        <h2 class="mt-1 font-black group-hover:text-amber-700">{{ $listing->title }}</h2>
                        <p class="mt-3 text-xl font-black text-amber-700">{{ number_format((float) $listing->price, 2, ',', ' ') }} <span class="text-sm">{{ $listing->currency }}</span></p>
                    </div>
                </a>
            @empty
                <div class="consumer-card sm:col-span-2 xl:col-span-3"><x-ui.empty message="Aucun article disponible pour le moment."/></div>
            @endforelse
        </div>
        <aside class="consumer-card h-fit p-5">
            <h2 class="font-black">Mes offres</h2>
            <div class="mt-3 space-y-3">
                @forelse($myOffers as $offer)
                    <a href="{{ route('consumer.marketplace.show', $offer->listing) }}" class="block rounded-2xl bg-slate-50 p-3 text-sm hover:bg-amber-50 dark:bg-slate-800"><p class="font-bold">{{ $offer->listing->title }}</p><p class="text-xs text-slate-500">{{ number_format((float) $offer->offered_price, 2, ',', ' ') }} {{ $offer->listing->currency }} · {{ $offer->statusLabel() }}</p></a>
                @empty
                    <p class="text-sm text-slate-500">Aucune offre envoyée.</p>
                @endforelse
            </div>
            <h2 class="mt-6 border-t pt-5 font-black">Mes commandes</h2><div class="mt-3 space-y-3">@forelse($myOrders as $order)<a href="{{ route('consumer.marketplace.show',$order->listing) }}" class="block rounded-xl bg-sky-50 p-3 text-sm"><strong>{{ $order->reference }}</strong><span class="block text-xs">{{ $order->listing->title }} · {{ $order->statusLabel() }}</span></a>@empty<p class="text-sm text-slate-500">Aucune commande.</p>@endforelse</div>
        </aside>
    </div>
    <div class="mt-8">{{ $listings->links() }}</div>
</x-consumer-layout>

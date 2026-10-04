<x-consumer-layout title="Explorer les vêtements">
    <section class="relative overflow-hidden rounded-[2rem] bg-slate-950 p-7 text-white sm:p-12">
        <div class="absolute inset-y-0 right-0 w-1/2 opacity-25"><img src="{{ asset('images/story/community-sorting.jpg') }}" alt="" class="h-full w-full object-cover"><div class="absolute inset-0 bg-gradient-to-r from-slate-950 to-transparent"></div></div>
        <div class="relative max-w-2xl"><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-300">La garde-robe circulaire</p><h1 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">Moins d’incertitude.<br>Plus de seconde vie.</h1><p class="mt-5 max-w-lg text-base leading-7 text-slate-300">Explorez les matières, comparez les scores et découvrez le potentiel de chaque vêtement.</p></div>
    </section>
    <section class="mt-8" aria-label="Catalogue des produits">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-xl font-bold">Les passeports textiles</h2><p class="mt-1 text-sm text-slate-500">{{ $products->total() }} produit(s) documenté(s)</p></div>
            <form class="flex w-full gap-2 sm:max-w-md" role="search"><div class="relative flex-1"><label for="catalog-search" class="sr-only">Rechercher un produit</label><x-icon name="search" class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-slate-400"/><input id="catalog-search" name="search" value="{{ request('search') }}" placeholder="Marque, produit, catégorie…" class="field !mt-0 py-3 pl-12"></div><button class="btn-primary">Rechercher</button></form>
        </div>
        @if(request('search'))<p class="mb-5 text-sm text-slate-500">Résultats pour « {{ request('search') }} » <a href="{{ route('consumer.products') }}" class="ml-2 font-bold text-emerald-600 underline">Effacer</a></p>@endif
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($products as $product)
            <article class="consumer-card group overflow-hidden">
                <div class="relative aspect-[4/3] overflow-hidden bg-slate-100 dark:bg-slate-800"><img src="{{ $product->image_url }}" alt="{{ $product->image_path ? $product->name : 'Illustration de la circularité textile' }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" loading="lazy"><span class="absolute left-4 top-4 rounded-full bg-slate-950/80 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur">{{ $product->category }}</span>@unless($product->image_path)<span class="absolute bottom-3 right-3 rounded bg-slate-950/70 px-2 py-1 text-[10px] text-white">Photo illustrative</span>@endunless</div>
                <div class="p-6"><p class="text-[11px] font-bold uppercase tracking-[.16em] text-emerald-600 dark:text-emerald-400">{{ $product->brand }}</p><h3 class="mt-2 text-xl font-bold tracking-tight">{{ $product->name }}</h3><div class="mt-4 flex min-h-7 flex-wrap gap-2">@foreach($product->materials->take(3) as $material)<span class="rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-600 dark:bg-slate-800">{{ $material->name }}</span>@endforeach</div>
                    <div class="mt-6 space-y-4 border-t border-slate-100 pt-5 dark:border-slate-800"><x-ui.score label="Circularité" :value="$product->circularity_score"/><x-ui.score label="Traçabilité" :value="$product->traceability_score"/><x-ui.score label="Sans matière animale" :value="$product->animal_free_score"/></div>
                    <div class="mt-5 flex flex-wrap gap-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300">@if($product->repairable)<span class="inline-flex items-center gap-1"><x-icon name="repair" class="h-4 w-4"/>Réparable</span>@endif @if($product->recyclable)<span class="inline-flex items-center gap-1"><x-icon name="recycle" class="h-4 w-4"/>Recyclable</span>@endif</div>
                </div>
            </article>
        @empty
            <div class="panel sm:col-span-2 lg:col-span-3"><x-ui.empty message="Aucun produit ne correspond à votre recherche."/></div>
        @endforelse
        </div><div class="mt-8">{{ $products->links() }}</div>
    </section>
</x-consumer-layout>

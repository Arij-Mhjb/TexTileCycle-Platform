<x-consumer-layout title="Découvrir et agir">
    <section class="relative overflow-hidden rounded-[2.25rem] bg-slate-950 px-6 py-10 text-white shadow-2xl shadow-slate-900/15 sm:px-10 lg:px-14 lg:py-14">
        <div class="absolute -right-24 -top-32 h-80 w-80 rounded-full bg-emerald-500/25 blur-3xl"></div>
        <div class="absolute -bottom-36 left-1/3 h-72 w-72 rounded-full bg-sky-400/15 blur-3xl"></div>
        <div class="relative grid items-center gap-10 lg:grid-cols-[1.2fr_.8fr]">
            <div class="animate-fade-up">
                <p class="text-xs font-black uppercase tracking-[.22em] text-emerald-300">Bonjour {{ str(auth()->user()->name)->before(' ') }}</p>
                <h1 class="mt-4 max-w-2xl text-4xl font-black leading-tight tracking-tight sm:text-5xl">Quel vêtement voulez-vous sauver aujourd’hui&nbsp;?</h1>
                <p class="mt-5 max-w-xl leading-7 text-slate-300">Photographiez son ticket ou son étiquette. Nous retrouvons sa composition et les meilleures solutions près de vous.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('scans.create') }}" class="animate-pulse-ring inline-flex items-center gap-2 rounded-2xl bg-emerald-500 px-5 py-3.5 text-sm font-black text-white transition hover:bg-emerald-400"><x-icon name="scan" class="h-5 w-5" /> Scanner un vêtement</a>
                    <a href="{{ route('consumer.points') }}" class="rounded-2xl border border-white/20 bg-white/5 px-5 py-3.5 text-sm font-black backdrop-blur transition hover:bg-white/10">Trouver un point</a>
                </div>
            </div>
            <div class="animate-float-soft rounded-3xl border border-white/10 bg-white/10 p-5 backdrop-blur-xl">
                <div class="rounded-2xl bg-white p-5 text-slate-900">
                    <div class="flex items-center justify-between"><div><p class="text-xs font-black uppercase tracking-wider text-emerald-600">Votre impact</p><p class="mt-1 text-lg font-black">Progression circulaire</p></div><span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"><x-icon name="recycle" class="h-6 w-6" /></span></div>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-2xl font-black">{{ $personalStats['weight'] }}<span class="text-sm text-slate-400"> kg</span></p><p class="mt-1 text-xs font-semibold text-slate-500">Textiles retournés</p></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><p class="text-2xl font-black text-emerald-600">{{ $personalStats['points'] }}</p><p class="mt-1 text-xs font-semibold text-slate-500">Points gagnés</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach([['Mes scans',$personalStats['scans'],'Analyses effectuées','scans.index','scan'],['Mes retours',$personalStats['returns'],'Dépôts déclarés','product-returns.index','refresh'],['Points',$personalStats['points'],'Récompenses cumulées','consumer.home','star'],['Poids sauvé',$personalStats['weight'].' kg','Textile valorisé','consumer.home','scale']] as $stat)
            <a href="{{ route($stat[3]) }}" class="consumer-card animate-fade-up p-5"><div class="flex items-center justify-between"><span class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"><x-icon :name="$stat[4]" class="h-5 w-5" /></span><x-icon name="arrow-up" class="h-4 w-4 text-slate-300" /></div><p class="mt-5 text-2xl font-black tracking-tight">{{ $stat[1] }}</p><p class="text-sm font-black text-slate-700">{{ $stat[0] }}</p><p class="mt-1 text-xs text-slate-400">{{ $stat[2] }}</p></a>
        @endforeach
    </section>

    <section class="mt-12">
        <div class="flex items-end justify-between"><div><p class="eyebrow">Actions recommandées</p><h2 class="mt-2 text-2xl font-black tracking-tight">Donnez une seconde vie à vos vêtements</h2></div></div>
        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <a href="{{ route('consumer.programs', ['type'=>'repair']) }}" class="consumer-card group overflow-hidden p-6"><div class="grid h-12 w-12 place-items-center rounded-2xl bg-amber-50 text-amber-700 transition group-hover:rotate-6 dark:bg-amber-950 dark:text-amber-300"><x-icon name="repair" class="h-6 w-6" /></div><h3 class="mt-5 text-lg font-black">Faire réparer</h3><p class="mt-2 text-sm leading-6 text-slate-500">Trouvez un atelier pour prolonger la durée de vie de votre vêtement.</p></a>
            <a href="{{ route('consumer.programs', ['type'=>'donation']) }}" class="consumer-card group overflow-hidden p-6"><div class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-sky-700 transition group-hover:rotate-6 dark:bg-sky-950 dark:text-sky-300"><x-icon name="heart" class="h-6 w-6" /></div><h3 class="mt-5 text-lg font-black">Faire un don</h3><p class="mt-2 text-sm leading-6 text-slate-500">Confiez les vêtements en bon état à une association partenaire.</p></a>
            <a href="{{ route('consumer.programs', ['type'=>'recycling']) }}" class="consumer-card group overflow-hidden p-6"><div class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 transition group-hover:rotate-6 dark:bg-emerald-950 dark:text-emerald-300"><x-icon name="recycle" class="h-6 w-6" /></div><h3 class="mt-5 text-lg font-black">Recycler</h3><p class="mt-2 text-sm leading-6 text-slate-500">Identifiez la filière adaptée à la composition du produit.</p></a>
        </div>
    </section>

    <section class="mt-12 grid gap-7 lg:grid-cols-[1.2fr_.8fr]">
        <div>
            <div class="flex items-end justify-between"><div><p class="eyebrow">Passeports textiles</p><h2 class="mt-2 text-2xl font-black">Produits récemment documentés</h2></div><a href="{{ route('consumer.products') }}" class="text-sm font-black text-emerald-600">Tout explorer</a></div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">@forelse($featuredProducts->take(4) as $product)<article class="consumer-card overflow-hidden"><img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-36 w-full object-cover"><div class="p-5"><div class="flex items-start justify-between"><span class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200"><x-icon name="shirt" class="h-5 w-5" /></span><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700">{{ $product->circularity_score ?? '—' }}/100</span></div><p class="mt-4 text-xs font-bold uppercase tracking-wider text-slate-400">{{ $product->brand }}</p><h3 class="mt-1 font-black">{{ $product->name }}</h3><div class="mt-4 flex gap-2 text-[11px] font-bold text-slate-500">@if($product->repairable)<span class="rounded-full bg-slate-100 px-2 py-1">Réparable</span>@endif @if($product->recyclable)<span class="rounded-full bg-slate-100 px-2 py-1">Recyclable</span>@endif</div></div></article>@empty<x-ui.empty/>@endforelse</div>
        </div>
        <div><p class="eyebrow">Près de vous</p><h2 class="mt-2 text-2xl font-black">Points de collecte</h2><div class="mt-5 space-y-3">@forelse($nearbyPoints as $point)<article class="consumer-card flex items-center gap-4 p-4"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-600 text-white"><x-icon name="map-pin" class="h-6 w-6" /></span><div class="min-w-0 flex-1"><p class="truncate font-black">{{ $point->name }}</p><p class="truncate text-xs text-slate-400">{{ $point->city }} · {{ $point->recoveryProgram->name }}</p></div><x-icon name="chevron-right" class="h-5 w-5 text-slate-300" /></article>@empty<x-ui.empty/>@endforelse</div><a href="{{ route('consumer.points') }}" class="mt-4 inline-flex text-sm font-black text-emerald-600">Voir tous les points →</a></div>
    </section>
</x-consumer-layout>

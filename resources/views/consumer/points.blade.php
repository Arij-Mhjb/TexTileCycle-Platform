<x-consumer-layout title="Points de collecte">
    <section class="overflow-hidden rounded-[2rem] bg-slate-950 text-white">
        <div class="grid lg:grid-cols-[.8fr_1.2fr]">
            <div class="p-8 sm:p-10">
                <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-300">Réseau TexTileCycle</p>
                <h1 class="mt-3 text-3xl font-black">Trouvez la solution adaptée</h1>
                <p class="mt-4 leading-7 text-slate-300">Filtrez les ateliers, associations, reprises de marque et centres de recyclage.</p>
                <form class="mt-7 grid gap-3 sm:grid-cols-2">
                    <input type="hidden" name="latitude" value="{{ $latitude }}"><input type="hidden" name="longitude" value="{{ $longitude }}">
                    <input name="city" value="{{ request('city') }}" placeholder="Ville…" class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-400">
                    <select name="type" class="rounded-2xl border border-white/10 bg-slate-900 px-4 py-3 text-sm text-white">
                        <option value="">Toutes les solutions</option>
                        @foreach (['repair'=>'Réparation','donation'=>'Don','reuse'=>'Réemploi','take_back'=>'Reprise marque','recycling'=>'Recyclage'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-black sm:col-span-2">Rechercher</button>
                </form>
                <button type="button" onclick="locateCollectionPoints()" class="mt-3 w-full rounded-2xl border border-emerald-300 px-5 py-3 text-sm font-black text-emerald-200">Utiliser ma position</button>
            </div>
            <div class="relative min-h-64 bg-[radial-gradient(circle_at_center,_rgba(16,185,129,.28),_transparent_58%)]">
                <div class="absolute inset-0 opacity-30" style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:28px 28px"></div>
                <div class="relative grid h-full place-items-center"><span class="animate-pulse-ring grid h-20 w-20 place-items-center rounded-full bg-emerald-500"><x-icon name="map-pin" class="h-9 w-9"/></span></div>
            </div>
        </div>
    </section>

    <div class="mt-8 grid gap-4 md:grid-cols-2">
        @forelse ($points as $point)
            <article class="consumer-card flex gap-4 p-5">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"><x-icon name="map-pin" class="h-7 w-7"/></span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <div><h2 class="font-black">{{ $point->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $point->address }}, {{ $point->city }}</p></div>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-black text-emerald-700">Actif</span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold text-slate-500">
                        <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ str($point->recoveryProgram->type)->replace('_',' ')->title() }}</span>
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700">+{{ $point->recoveryProgram->reward_points }} pts validés après traitement</span>
                        @if ($point->opening_hours)<span class="rounded-full bg-slate-100 px-2.5 py-1">{{ $point->opening_hours }}</span>@endif
                        @if (isset($point->distance_km))<span class="rounded-full bg-sky-50 px-2.5 py-1 text-sky-700">{{ $point->distance_km }} km</span>@endif
                    </div>
                    <a href="{{ route('product-returns.create', ['point'=>$point->id,'action'=>$point->recoveryProgram->type]) }}" class="mt-4 inline-flex text-sm font-black text-emerald-600">Préparer un dépôt →</a>
                    @if($point->latitude && $point->longitude)<a target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $point->latitude }},{{ $point->longitude }}" class="ml-4 mt-4 inline-flex text-sm font-black text-sky-600">Itinéraire →</a>@endif
                </div>
            </article>
        @empty
            <div class="md:col-span-2"><x-ui.empty message="Aucun point trouvé avec ces critères."/></div>
        @endforelse
    </div>
    <div class="mt-8">{{ $points->links() }}</div>
</x-consumer-layout>

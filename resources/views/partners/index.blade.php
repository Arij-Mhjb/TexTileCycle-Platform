<x-app-layout title="Partenaires circulaires">
    <x-ui.page-heading
        title="Partenaires circulaires"
        description="Réseau de structures de réparation, de tri et de réemploi pour les textiles en fin de vie."
    />

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse($partners as $partner)
            <article class="panel overflow-hidden">
                <div class="border-b border-slate-100 p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">{{ $partner->type }}</p>
                            <h3 class="mt-2 text-xl font-black text-slate-900">{{ $partner->name }}</h3>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                            {{ $partner->workshops_count }} atelier(s)
                        </span>
                    </div>
                </div>
                <div class="space-y-4 p-5">
                    <div class="flex items-start gap-3 text-sm text-slate-600">
                        <span class="mt-0.5 text-emerald-600">📍</span>
                        <span>{{ $partner->city }} · {{ $partner->address }}</span>
                    </div>
                    <p class="text-sm leading-6 text-slate-600">{{ Str::limit($partner->description ?? 'Aucun descriptif disponible.', 180) }}</p>
                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('partners.show', $partner) }}" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-700 hover:text-emerald-800">
                            Voir le détail
                            <span>→</span>
                        </a>
                        <span class="text-xs font-semibold uppercase tracking-[.18em] text-slate-400">{{ $partner->city }}</span>
                    </div>
                </div>
            </article>
        @empty
            <div class="panel col-span-full p-8 text-sm text-slate-500">
                Aucun partenaire circulaire n’est encore référencé.
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $partners->links() }}
    </div>
</x-app-layout>

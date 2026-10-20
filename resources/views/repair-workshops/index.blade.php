<x-app-layout title="Ateliers de réparation">
    <x-ui.page-heading
        title="Ateliers de réparation"
        description="Liste des ateliers, structures et lieux de réparation textile disponibles."
    />

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse($items as $item)
            <article class="panel overflow-hidden">
                <div class="p-5">
                    <p class="text-xs font-black uppercase tracking-[.18em] text-emerald-600">{{ $item->partner?->name ?? 'Partenaire non renseigné' }}</p>
                    <h3 class="mt-2 text-xl font-black text-slate-900">{{ $item->name }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ Str::limit($item->description ?? 'Atelier de réparation textile local.', 160) }}</p>
                </div>
                <div class="border-t border-slate-100 p-5 text-sm text-slate-600">
                    <p>📍 {{ $item->city }} · {{ $item->address }}</p>
                    <p class="mt-2">🧵 {{ $item->specialties ?? 'Réparation textile' }}</p>
                    <p class="mt-2">📦 Capacité : {{ $item->capacity_per_week ?? 0 }} / semaine</p>
                    <div class="mt-4">
                        <a href="{{ route('public-workshops.show', $item) }}" class="inline-flex items-center gap-2 font-bold text-emerald-700 hover:text-emerald-800">
                            Voir le détail
                            <span>→</span>
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="panel col-span-full p-8 text-sm text-slate-500">Aucun atelier de réparation n’est disponible actuellement.</div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $items->links() }}
    </div>
</x-app-layout>

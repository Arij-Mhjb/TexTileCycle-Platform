<x-app-layout title="{{ $partner->name }}">
    <div class="space-y-6">
        <div class="panel p-6 sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">{{ $partner->type }}</p>
                    <h1 class="mt-3 text-3xl font-black text-slate-900">{{ $partner->name }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">{{ $partner->description }}</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                    {{ $partner->workshops->count() }} ateliers référencés
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
            <div class="panel p-6">
                <h2 class="text-xl font-black text-slate-900">Ateliers associés</h2>
                <div class="mt-5 space-y-4">
                    @forelse($partner->workshops as $workshop)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">{{ $workshop->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $workshop->specialties ?? 'Réparation textile' }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                                    {{ $workshop->capacity_per_week ?? 0 }} / sem.
                                </span>
                            </div>
                            <div class="mt-3 space-y-2 text-sm text-slate-600">
                                <p>📍 {{ $workshop->city }} · {{ $workshop->address }}</p>
                                <p>📞 {{ $workshop->phone ?? 'Non communiqué' }}</p>
                                <p>✉️ {{ $workshop->email ?? 'Non communiqué' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aucun atelier n’est associé à ce partenaire pour le moment.</p>
                    @endforelse
                </div>
            </div>

            <aside class="panel p-6">
                <h2 class="text-xl font-black text-slate-900">Informations</h2>
                <dl class="mt-5 space-y-4 text-sm text-slate-600">
                    <div>
                        <dt class="font-bold text-slate-800">Ville</dt>
                        <dd class="mt-1">{{ $partner->city }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-800">Adresse</dt>
                        <dd class="mt-1">{{ $partner->address }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-800">Contact</dt>
                        <dd class="mt-1">{{ $partner->contact_name ?? 'À définir' }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-800">Site web</dt>
                        <dd class="mt-1"><a href="{{ $partner->website ?? '#' }}" class="text-emerald-700 hover:underline" target="_blank" rel="noopener">{{ $partner->website ?? 'Non renseigné' }}</a></dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</x-app-layout>

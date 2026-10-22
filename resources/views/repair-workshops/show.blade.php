<x-app-layout title="{{ $workshop->name }}">
    <div class="space-y-6">
        <div class="panel p-6 sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">{{ $workshop->partner?->name ?? 'Partenaire circulaire' }}</p>
                    <h1 class="mt-3 text-3xl font-black text-slate-900">{{ $workshop->name }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">{{ $workshop->description ?? 'Aucun descriptif disponible.' }}</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                    {{ $workshop->capacity_per_week ?? 0 }} / semaine
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
            <div class="panel p-6">
                <h2 class="text-xl font-black text-slate-900">Informations</h2>
                <dl class="mt-5 space-y-4 text-sm text-slate-600">
                    <div>
                        <dt class="font-bold text-slate-800">Spécialités</dt>
                        <dd class="mt-1">{{ $workshop->specialties ?? 'Réparation textile' }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-800">Ville</dt>
                        <dd class="mt-1">{{ $workshop->city }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-800">Adresse</dt>
                        <dd class="mt-1">{{ $workshop->address }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold text-slate-800">Contact</dt>
                        <dd class="mt-1">{{ $workshop->contact_name ?? 'À définir' }}</dd>
                    </div>
                </dl>
            </div>

            <aside class="panel p-6">
                <h2 class="text-xl font-black text-slate-900">Coordonnées</h2>
                <div class="mt-5 space-y-4 text-sm text-slate-600">
                    <p><span class="font-bold text-slate-800">Téléphone :</span> {{ $workshop->phone ?? 'Non communiqué' }}</p>
                    <p><span class="font-bold text-slate-800">Email :</span> {{ $workshop->email ?? 'Non communiqué' }}</p>
                    <p><span class="font-bold text-slate-800">Partenaire :</span> {{ $workshop->partner?->name ?? '—' }}</p>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>

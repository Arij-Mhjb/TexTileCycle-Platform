<x-app-layout title="{{ $item->name }}">
    <x-ui.page-heading
        title="{{ $item->name }}"
        description="Détail de l’atelier de réparation et de son partenaire associé."
        :action="route('admin.repair-workshops.index')"
        action-label="Retour à la liste"
    />

    <div class="grid gap-6 xl:grid-cols-[1.3fr_.7fr]">
        <div class="panel p-6 sm:p-8">
            <p class="text-xs font-black uppercase tracking-[.2em] text-emerald-600">{{ $item->partner?->name ?? 'Partenaire non renseigné' }}</p>
            <h2 class="mt-3 text-2xl font-black text-slate-900">{{ $item->name }}</h2>
            <p class="mt-3 text-sm leading-7 text-slate-600">{{ $item->description ?? 'Aucune description disponible.' }}</p>

            <div class="mt-6 space-y-4 text-sm text-slate-600">
                <p><span class="font-bold text-slate-800">Ville :</span> {{ $item->city }}</p>
                <p><span class="font-bold text-slate-800">Adresse :</span> {{ $item->address }}</p>
                <p><span class="font-bold text-slate-800">Spécialités :</span> {{ $item->specialties ?? 'Réparation textile' }}</p>
                <p><span class="font-bold text-slate-800">Contact :</span> {{ $item->contact_name ?? 'À définir' }}</p>
                <p><span class="font-bold text-slate-800">Email :</span> {{ $item->email ?? 'Non renseigné' }}</p>
                <p><span class="font-bold text-slate-800">Téléphone :</span> {{ $item->phone ?? 'Non renseigné' }}</p>
                <p><span class="font-bold text-slate-800">Capacité / semaine :</span> {{ $item->capacity_per_week ?? 0 }}</p>
            </div>
        </div>

        <aside class="panel p-6">
            <h3 class="text-lg font-black text-slate-900">Partenaire</h3>
            <div class="mt-4 rounded-2xl border border-slate-200 p-4">
                <p class="font-bold text-slate-800">{{ $item->partner?->name ?? '—' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $item->partner?->city ?? 'Ville inconnue' }}</p>
            </div>
        </aside>
    </div>
</x-app-layout>

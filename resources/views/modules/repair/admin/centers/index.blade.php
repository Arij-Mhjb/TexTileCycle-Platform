<x-app-layout title="Centres de réparation">
    <x-module.hero module="repair" title="Centres de réparation partenaires" description="Ateliers de couture, cordonniers et reconditionneurs qui donnent une seconde vie aux articles.">
        <x-slot:actions><x-module.button module="repair" variant="light" :href="route('admin.repair-centers.create')">+ Ajouter un centre</x-module.button></x-slot:actions>
    </x-module.hero>
    @include('modules.repair.admin._tabs')
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($centers as $center)
            <article class="panel p-6">
                <div class="flex items-start justify-between gap-3"><div class="grid h-12 w-12 place-items-center rounded-2xl bg-amber-50 text-amber-700"><x-icon name="repair" class="h-6 w-6"/></div><x-module.badge :tone="$center->is_active ? 'good' : 'neutral'">{{ $center->is_active ? 'Actif' : 'Inactif' }}</x-module.badge></div>
                <h2 class="mt-4 text-lg font-black">{{ $center->name }}</h2>
                <p class="text-sm text-slate-500">{{ collect([$center->address, $center->city])->filter()->join(', ') }}</p>
                @if($center->specialties)<p class="mt-2 text-xs font-bold text-amber-700">{{ $center->specialties }}</p>@endif
                <x-module.bar class="mt-5" module="repair" label="Charge de la semaine" :value="$center->active_orders_count" :max="$center->capacity_per_week" :display="$center->active_orders_count.' / '.$center->capacity_per_week"/>
                <p class="mt-2 text-xs text-slate-400">{{ $center->repair_orders_count }} commande(s) au total</p>
                <div class="mt-4 flex gap-2"><a href="{{ route('admin.repair-centers.edit', $center) }}" class="rounded-lg px-3 py-2 text-sm font-bold text-amber-700 hover:bg-amber-50">Modifier</a><form method="POST" action="{{ route('admin.repair-centers.destroy', $center) }}" onsubmit="return confirm('Supprimer ce centre ?')">@csrf @method('DELETE')<button class="btn-danger">Supprimer</button></form></div>
            </article>
        @empty
            <div class="panel md:col-span-2 xl:col-span-3"><x-ui.empty message="Aucun centre de réparation."/></div>
        @endforelse
    </div>
</x-app-layout>

<x-app-layout :title="$center->exists ? 'Modifier le centre' : 'Nouveau centre'">
    <x-module.hero module="repair" :title="$center->exists ? 'Modifier · '.$center->name : 'Nouveau centre de réparation'"/>
    @include('modules.repair.admin._tabs')
    <form method="POST" action="{{ $center->exists ? route('admin.repair-centers.update', $center) : route('admin.repair-centers.store') }}" class="panel p-6 sm:p-8">
        @csrf @if($center->exists) @method('PUT') @endif
        <div class="grid gap-5 md:grid-cols-2">
            <x-module.field name="name" label="Nom" :value="$center->name" required/>
            <x-module.field name="city" label="Ville" :value="$center->city" required/>
            <x-module.field name="address" label="Adresse" :value="$center->address"/>
            <x-module.field name="specialties" label="Spécialités" :value="$center->specialties" placeholder="Couture, cuir, chaussures…"/>
            <x-module.field name="contact_email" type="email" label="E-mail" :value="$center->contact_email"/>
            <x-module.field name="phone" label="Téléphone" :value="$center->phone"/>
            <x-module.field name="capacity_per_week" type="number" min="1" label="Capacité / semaine" :value="$center->capacity_per_week" required/>
            <div class="flex items-end"><x-ui.checkbox name="is_active" label="Centre actif" :checked="$center->is_active"/></div>
        </div>
        <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6"><a href="{{ route('admin.repair-centers.index') }}" class="btn-secondary">Annuler</a><x-module.button module="repair">Enregistrer</x-module.button></div>
    </form>
</x-app-layout>

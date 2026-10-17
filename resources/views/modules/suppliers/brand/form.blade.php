<x-brand-layout>
    <x-module.hero module="suppliers" :title="$supplier->exists ? 'Modifier · '.$supplier->name : 'Nouveau fournisseur'" description="Identité, localisation et certifications déclarées du fournisseur."/>
    @include('modules.suppliers.brand._tabs')

    <form method="POST" action="{{ $supplier->exists ? route('brand.suppliers.update', $supplier) : route('brand.suppliers.store') }}" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        @if($supplier->exists) @method('PUT') @endif
        <div class="grid gap-5 md:grid-cols-2">
            <x-module.field name="name" label="Raison sociale" :value="$supplier->name" required maxlength="150"/>
            <x-ui.select name="status" label="Statut" :value="$supplier->status" :options="['active' => 'Actif', 'under_review' => 'En revue', 'suspended' => 'Suspendu']" required/>
            <x-module.field name="contact_name" label="Contact" :value="$supplier->contact_name"/>
            <x-module.field name="email" type="email" label="E-mail" :value="$supplier->email"/>
            <x-module.field name="phone" label="Téléphone" :value="$supplier->phone"/>
            <x-module.field name="country" label="Pays" :value="$supplier->country" required/>
            <x-module.field name="city" label="Ville" :value="$supplier->city"/>
            <x-module.field name="address" label="Adresse" :value="$supplier->address"/>
            <div class="md:col-span-2"><x-module.field name="certifications" label="Certifications déclarées" :value="$supplier->certifications" placeholder="GOTS, OEKO-TEX, GRS…"/></div>
            <div class="md:col-span-2"><x-ui.textarea name="notes" label="Notes internes" :value="$supplier->notes" rows="3"/></div>
        </div>
        <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
            <a href="{{ $supplier->exists ? route('brand.suppliers.show', $supplier) : route('brand.suppliers.index') }}" class="btn-secondary">Annuler</a>
            <x-module.button module="suppliers">Enregistrer</x-module.button>
        </div>
    </form>
</x-brand-layout>

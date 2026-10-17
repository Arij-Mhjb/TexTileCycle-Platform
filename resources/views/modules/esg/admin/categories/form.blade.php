<x-app-layout :title="$category->exists ? 'Modifier la catégorie' : 'Nouvelle catégorie'">
    <x-module.hero module="esg" :title="$category->exists ? 'Modifier · '.$category->name : 'Nouvelle catégorie d’impact'" description="Utilisez les codes co2, water, energy ou waste pour qu’ils soient pris en compte dans le calcul."/>
    @include('modules.esg.admin._tabs')
    <form method="POST" action="{{ $category->exists ? route('admin.impact-categories.update', $category) : route('admin.impact-categories.store') }}" class="panel p-6 sm:p-8">
        @csrf @if($category->exists) @method('PUT') @endif
        <div class="grid gap-5 md:grid-cols-2">
            <x-module.field name="code" label="Code" :value="$category->code" required placeholder="co2"/>
            <x-module.field name="name" label="Nom" :value="$category->name" required/>
            <x-module.field name="unit" label="Unité" :value="$category->unit" required placeholder="kg CO₂e"/>
            <x-module.field name="weight" type="number" step="0.01" min="0" max="1" label="Poids dans le score (0 à 1)" :value="$category->weight" required/>
            <div class="md:col-span-2"><x-ui.textarea name="description" label="Description" :value="$category->description" rows="3"/></div>
        </div>
        <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6"><a href="{{ route('admin.impact-categories.index') }}" class="btn-secondary">Annuler</a><x-module.button module="esg">Enregistrer</x-module.button></div>
    </form>
</x-app-layout>

<x-app-layout title="Modifier l’atelier">
    <x-ui.page-heading title="Modifier l’atelier" description="Mettre à jour les informations de l’atelier." />
    <form method="POST" action="{{ route('admin.repair-workshops.update', $item) }}" class="panel p-6 sm:p-8">
        @csrf @method('PUT')
        @include('admin.repair-workshops._form', ['item' => $item])
        <x-ui.form-actions :cancel="route('admin.repair-workshops.index')" />
    </form>
</x-app-layout>

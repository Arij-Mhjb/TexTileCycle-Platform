<x-app-layout title="Modifier le partenaire">
    <x-ui.page-heading title="Modifier le partenaire" description="Mettre à jour les informations du partenaire." />
    <form method="POST" action="{{ route('admin.repair-partners.update', $item) }}" class="panel p-6 sm:p-8">
        @csrf @method('PUT')
        @include('admin.repair-partners._form', ['item' => $item])
        <x-ui.form-actions :cancel="route('admin.repair-partners.index')" />
    </form>
</x-app-layout>

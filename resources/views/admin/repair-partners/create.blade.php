<x-app-layout title="Nouveau partenaire">
    <x-ui.page-heading title="Nouveau partenaire" description="Créer un partenaire circulaire pour les ateliers et la réparation textile." />
    <form method="POST" action="{{ route('admin.repair-partners.store') }}" class="panel p-6 sm:p-8">
        @csrf
        @include('admin.repair-partners._form')
        <x-ui.form-actions :cancel="route('admin.repair-partners.index')" />
    </form>
</x-app-layout>

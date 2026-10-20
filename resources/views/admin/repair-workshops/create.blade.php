<x-app-layout title="Nouvel atelier">
    <x-ui.page-heading title="Nouvel atelier" description="Créer un atelier de réparation textile rattaché à un partenaire." />
    <form method="POST" action="{{ route('admin.repair-workshops.store') }}" class="panel p-6 sm:p-8">
        @csrf
        @include('admin.repair-workshops._form')
        <x-ui.form-actions :cancel="route('admin.repair-workshops.index')" />
    </form>
</x-app-layout>

<x-app-layout title="Nouvelle catégorie">
    <x-ui.page-heading title="Nouvelle catégorie" description="Créer une catégorie pour classer les articles du blog." />
    <form method="POST" action="{{ route('admin.categories.store') }}" class="panel p-6 sm:p-8">
        @csrf
        @include('admin.categories._form')
        <x-ui.form-actions :cancel="route('admin.categories.index')" />
    </form>
</x-app-layout>

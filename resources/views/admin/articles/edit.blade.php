<x-app-layout title="Modifier l’article">
    <x-ui.page-heading title="Modifier l’article" description="Mettre à jour le contenu de l’article." />
    <form method="POST" action="{{ route('admin.articles.update', $item) }}" class="panel p-6 sm:p-8">
        @csrf @method('PUT')
        @include('admin.articles._form', ['item' => $item, 'categories' => $categories])
        <x-ui.form-actions :cancel="route('admin.articles.index')" />
    </form>
</x-app-layout>

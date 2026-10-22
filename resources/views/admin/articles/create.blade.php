<x-app-layout title="Nouvel article">
    <x-ui.page-heading title="Nouvel article" description="Rédiger un nouvel article de sensibilisation." />
    <form method="POST" action="{{ route('admin.articles.store') }}" class="panel p-6 sm:p-8">
        @csrf
        @include('admin.articles._form', ['item' => $item, 'categories' => $categories])
        <x-ui.form-actions :cancel="route('admin.articles.index')" />
    </form>
</x-app-layout>

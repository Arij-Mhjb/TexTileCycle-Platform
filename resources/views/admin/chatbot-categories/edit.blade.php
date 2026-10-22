<x-app-layout title="Modifier la catégorie">
    <x-ui.page-heading title="Modifier la catégorie" description="Mettre à jour les informations de la catégorie." />
    <form method="POST" action="{{ route('admin.chatbot-categories.update', $item) }}" class="panel p-6 sm:p-8">
        @csrf @method('PUT')
        @include('admin.chatbot-categories._form', ['item' => $item])
        <x-ui.form-actions :cancel="route('admin.chatbot-categories.index')" />
    </form>
</x-app-layout>

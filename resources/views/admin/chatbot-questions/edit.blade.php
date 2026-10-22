<x-app-layout title="Modifier la question">
    <x-ui.page-heading title="Modifier la question" description="Mettre à jour la réponse envoyée par l'assistant virtuel." />
    <form method="POST" action="{{ route('admin.chatbot-questions.update', $item) }}" class="panel p-6 sm:p-8">
        @csrf @method('PUT')
        @include('admin.chatbot-questions._form', ['item' => $item, 'categories' => $categories])
        <x-ui.form-actions :cancel="route('admin.chatbot-questions.index')" />
    </form>
</x-app-layout>

<x-app-layout title="Nouvelle question">
    <x-ui.page-heading title="Nouvelle question" description="Ajouter une question/réponse statique à l'assistant virtuel." />
    <form method="POST" action="{{ route('admin.chatbot-questions.store') }}" class="panel p-6 sm:p-8">
        @csrf
        @include('admin.chatbot-questions._form', ['item' => $item, 'categories' => $categories])
        <x-ui.form-actions :cancel="route('admin.chatbot-questions.index')" />
    </form>
</x-app-layout>

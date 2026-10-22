<x-app-layout title="Nouvelle catégorie">
    <x-ui.page-heading title="Nouvelle catégorie" description="Créer une catégorie pour classer les questions du chatbot." />
    <form method="POST" action="{{ route('admin.chatbot-categories.store') }}" class="panel p-6 sm:p-8">
        @csrf
        @include('admin.chatbot-categories._form')
        <x-ui.form-actions :cancel="route('admin.chatbot-categories.index')" />
    </form>
</x-app-layout>

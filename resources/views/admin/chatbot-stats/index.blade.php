<x-app-layout title="Statistiques du chatbot">
    <x-ui.page-heading title="Statistiques du chatbot" description="Suivez les questions les plus posées et le taux de réussite de l'assistant virtuel." />

    <section class="grid gap-4 sm:grid-cols-3">
        <article class="panel p-5"><p class="text-xs font-black uppercase tracking-wider text-emerald-600">Questions actives</p><p class="mt-2 text-2xl font-black">{{ $totalQuestions }}</p></article>
        <article class="panel p-5"><p class="text-xs font-black uppercase tracking-wider text-sky-600">Conversations</p><p class="mt-2 text-2xl font-black">{{ $totalConversations }}</p></article>
        <article class="panel p-5"><p class="text-xs font-black uppercase tracking-wider text-violet-600">Taux de réponse</p><p class="mt-2 text-2xl font-black">{{ $matchRate }}%</p></article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
        <article class="panel p-5 sm:p-6">
            <h3 class="mb-5 text-lg font-black">Volume des 14 derniers jours</h3>
            <div class="flex h-48 items-end gap-2">
                @foreach($volume as $day)
                    <div class="flex flex-1 flex-col items-center gap-2">
                        <div class="w-full rounded-t-lg bg-emerald-500/80 dark:bg-emerald-500" style="height: {{ max(4, round($day['count'] / $maxVolume * 100)) }}%"></div>
                        <span class="text-[10px] font-semibold text-slate-400">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="panel p-5 sm:p-6">
            <h3 class="mb-4 text-lg font-black">Questions les plus posées</h3>
            <ol class="space-y-3">
                @forelse($topQuestions as $question)
                    <li class="flex items-center justify-between gap-3 text-sm">
                        <span class="truncate font-semibold text-slate-700 dark:text-slate-200">{{ $question->question }}</span>
                        <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">{{ $question->logs_count }}</span>
                    </li>
                @empty
                    <x-ui.empty />
                @endforelse
            </ol>
        </article>
    </section>

    <section class="mt-6">
        <article class="panel p-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-black">Questions sans réponse trouvée</h3>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">À traiter</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr><th class="px-2 py-2 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Question posée</th><th class="px-2 py-2 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Occurrences</th><th class="px-2 py-2 text-right text-xs font-bold uppercase tracking-wider text-slate-400">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($unmatched as $entry)
                            <tr>
                                <td class="px-2 py-3 text-slate-700 dark:text-slate-200">{{ $entry->question_text }}</td>
                                <td class="px-2 py-3 text-slate-500">{{ $entry->total }}</td>
                                <td class="px-2 py-3 text-right"><a href="{{ route('admin.chatbot-questions.create') }}" class="btn-secondary !px-3 !py-2">Créer une réponse</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-2 py-8 text-center text-slate-500">Toutes les questions récentes ont trouvé une réponse.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</x-app-layout>

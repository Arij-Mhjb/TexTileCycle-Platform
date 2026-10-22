<x-app-layout title="Statistiques du blog">
    <x-ui.page-heading title="Statistiques du blog" description="Suivez les vues et les retours des visiteurs sur les articles." />

    <section class="grid gap-4 sm:grid-cols-3">
        <article class="panel p-5"><p class="text-xs font-black uppercase tracking-wider text-emerald-600">Vues cumulées</p><p class="mt-2 text-2xl font-black">{{ $totalViews }}</p></article>
        <article class="panel p-5"><p class="text-xs font-black uppercase tracking-wider text-sky-600">J’aime</p><p class="mt-2 text-2xl font-black">{{ $totalLikes }}</p></article>
        <article class="panel p-5"><p class="text-xs font-black uppercase tracking-wider text-rose-600">Je n’aime pas</p><p class="mt-2 text-2xl font-black">{{ $totalDislikes }}</p></article>
    </section>

    <section class="mt-6">
        <article class="panel p-5 sm:p-6">
            <h3 class="mb-5 text-lg font-black">Articles les plus vus</h3>
            <div class="space-y-4">
                @forelse($topArticles as $article)
                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="truncate font-semibold text-slate-700 dark:text-slate-200">{{ $article->title }}</span>
                            <span class="shrink-0 text-xs text-slate-400">{{ $article->views_count }} vues · {{ $article->likes_count }} j’aime · {{ $article->dislikes_count }} je n’aime pas</span>
                        </div>
                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ max(2, round($article->views_count / $maxViews * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty />
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-6">
        <article class="panel overflow-hidden">
            <div class="border-b border-slate-100 p-5 dark:border-slate-800"><h3 class="text-lg font-black">Détail par article</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Article</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Catégorie</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Vues</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">J’aime</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-400">Je n’aime pas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topArticles as $article)
                            <tr>
                                <td class="px-5 py-3 font-semibold text-slate-700 dark:text-slate-200">{{ $article->title }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $article->category?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $article->views_count }}</td>
                                <td class="px-5 py-3 text-emerald-600">{{ $article->likes_count }}</td>
                                <td class="px-5 py-3 text-rose-600">{{ $article->dislikes_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucun article publié pour le moment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</x-app-layout>

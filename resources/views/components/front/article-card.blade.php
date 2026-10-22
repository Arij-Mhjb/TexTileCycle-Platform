@props(['article', 'reaction' => null])

<article class="panel overflow-hidden">
    <div class="p-5">
        <p class="text-xs font-black uppercase tracking-[.18em] text-emerald-600">{{ $article->category?->name ?? 'Non classé' }}</p>
        <h3 class="mt-2 text-xl font-black text-slate-900 dark:text-white">{{ $article->title }}</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $article->excerpt ?? Str::limit($article->content, 140) }}</p>
    </div>
    <div class="border-t border-slate-100 p-5 text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
        <p>{{ $article->published_at?->translatedFormat('d M Y') }} @if($article->author) · {{ $article->author->name }} @endif</p>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('blog.show', $article) }}" class="inline-flex items-center gap-2 font-bold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400">
                Lire l’article
                <span>→</span>
            </a>
            <x-front.article-reactions :article="$article" :reaction="$reaction" />
        </div>
    </div>
</article>

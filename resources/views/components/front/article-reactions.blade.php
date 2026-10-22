@props(['article', 'reaction' => null])

<div
    x-data="articleReactions({
        reactUrl: '{{ route('blog.react', $article) }}',
        likes: {{ $article->likes_count ?? 0 }},
        dislikes: {{ $article->dislikes_count ?? 0 }},
        userReaction: {{ $reaction ? "'{$reaction}'" : 'null' }},
    })"
    class="flex items-center gap-2"
>
    <button
        type="button"
        @click="react('like')"
        :disabled="loading"
        :class="userReaction === 'like' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 dark:bg-white/10 dark:text-slate-300'"
        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-bold transition"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 10v11M2 11h4.5a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2v-10ZM13 4l-1 6h8.5a1.5 1.5 0 0 1 1.46 1.84l-1.68 7A1.5 1.5 0 0 1 18.82 20H9V10l4-6Z"/></svg>
        <span x-text="likes"></span>
    </button>
    <button
        type="button"
        @click="react('dislike')"
        :disabled="loading"
        :class="userReaction === 'dislike' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-rose-50 hover:text-rose-700 dark:bg-white/10 dark:text-slate-300'"
        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-bold transition"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 14V3M22 13h-4.5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1H22V13ZM11 20l1-6H3.5A1.5 1.5 0 0 1 2.04 12.16l1.68-7A1.5 1.5 0 0 1 5.18 4H15v10l-4 6Z"/></svg>
        <span x-text="dislikes"></span>
    </button>
</div>

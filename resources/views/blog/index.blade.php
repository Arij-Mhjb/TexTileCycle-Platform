@extends('layouts.front')

@section('title', 'Blog')

@section('content')
    <div class="mb-10">
        <p class="text-xs font-black uppercase tracking-[.18em] text-emerald-600">Sensibilisation</p>
        <h1 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">Le blog TexTileCycle</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">Conseils, retours d’expérience et décryptages pour comprendre la circularité textile.</p>
    </div>

    <div class="mb-8 flex flex-wrap gap-2">
        <a href="{{ route('blog.index') }}" class="rounded-full px-4 py-2 text-sm font-bold {{ request('category') ? 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300' : 'bg-emerald-600 text-white' }}">Toutes les catégories</a>
        @foreach($categories as $category)
            <a href="{{ route('blog.index', ['category' => $category->slug]) }}" class="rounded-full px-4 py-2 text-sm font-bold {{ request('category') === $category->slug ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300' }}">{{ $category->name }}</a>
        @endforeach
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse($items as $article)
            <x-front.article-card :article="$article" :reaction="$reactionsByArticle[$article->id] ?? null" />
        @empty
            <div class="panel col-span-full p-8 text-sm text-slate-500">Aucun article publié pour le moment.</div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $items->links() }}
    </div>
@endsection

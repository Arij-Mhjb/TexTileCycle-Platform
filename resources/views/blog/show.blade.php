@extends('layouts.front')

@section('title', $article->title)

@section('content')
    <a href="{{ route('blog.index') }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400">← Retour au blog</a>

    <article class="panel mt-6 p-6 sm:p-10">
        <p class="text-xs font-black uppercase tracking-[.18em] text-emerald-600">{{ $article->category?->name ?? 'Non classé' }}</p>
        <h1 class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ $article->title }}</h1>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $article->published_at?->translatedFormat('d M Y') }}
                @if($article->author) · par {{ $article->author->name }} @endif
            </p>
            <x-front.article-reactions :article="$article" :reaction="$reaction" />
        </div>

        <div class="mt-8 max-w-none space-y-4 text-base leading-7 text-slate-700 dark:text-slate-200">
            @foreach(explode("\n\n", $article->content) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </article>

    @if($related->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Articles similaires</h2>
            <div class="mt-6 grid gap-6 md:grid-cols-3">
                @foreach($related as $relatedArticle)
                    <x-front.article-card :article="$relatedArticle" />
                @endforeach
            </div>
        </div>
    @endif
@endsection

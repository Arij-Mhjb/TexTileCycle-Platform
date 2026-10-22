<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleReaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $items = Article::with('category')
            ->withCount([
                'reactions as likes_count' => fn ($query) => $query->where('type', 'like'),
                'reactions as dislikes_count' => fn ($query) => $query->where('type', 'dislike'),
            ])
            ->published()
            ->when($request->filled('category'), fn ($query) => $query->whereHas(
                'category',
                fn ($q) => $q->where('slug', $request->string('category'))
            ))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $reactionsByArticle = ArticleReaction::where('session_id', $request->session()->getId())
            ->whereIn('article_id', $items->getCollection()->pluck('id'))
            ->pluck('type', 'article_id');

        $categories = Category::orderBy('name')->get();

        return view('blog.index', compact('items', 'categories', 'reactionsByArticle'));
    }

    public function show(Request $request, Article $article): View
    {
        abort_unless($article->published_at && $article->published_at->isPast(), 404);

        $article->load('category', 'author');
        $article->loadCount([
            'reactions as likes_count' => fn ($query) => $query->where('type', 'like'),
            'reactions as dislikes_count' => fn ($query) => $query->where('type', 'dislike'),
        ]);

        $viewedArticles = $request->session()->get('viewed_articles', []);
        if (! in_array($article->id, $viewedArticles, true)) {
            $article->increment('views_count');
            $request->session()->put('viewed_articles', [...$viewedArticles, $article->id]);
        }

        $reaction = $article->reactionFor($request->session()->getId());

        $related = Article::where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->withCount([
                'reactions as likes_count' => fn ($query) => $query->where('type', 'like'),
                'reactions as dislikes_count' => fn ($query) => $query->where('type', 'dislike'),
            ])
            ->published()
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('blog.show', compact('article', 'related', 'reaction'));
    }
}

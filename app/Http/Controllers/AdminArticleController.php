<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminArticleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Article::with('category');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->string('search') . '%');
        }

        $items = $query->latest()->paginate(10)->withQueryString();

        return view('admin.articles.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.articles.create', [
            'item' => new Article,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        Article::create($request->validated() + ['author_id' => $request->user()?->id]);

        return to_route('admin.articles.index')->with('success', 'Article créé.');
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.edit', [
            'item' => $article,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $article->update($request->validated());

        return to_route('admin.articles.index')->with('success', 'Article mis à jour.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return back()->with('success', 'Article supprimé.');
    }
}

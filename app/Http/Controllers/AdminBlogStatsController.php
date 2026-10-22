<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleReaction;
use Illuminate\View\View;

class AdminBlogStatsController extends Controller
{
    public function __invoke(): View
    {
        $totalViews = (int) Article::sum('views_count');
        $totalLikes = ArticleReaction::where('type', 'like')->count();
        $totalDislikes = ArticleReaction::where('type', 'dislike')->count();

        $topArticles = Article::with('category')
            ->withCount([
                'reactions as likes_count' => fn ($query) => $query->where('type', 'like'),
                'reactions as dislikes_count' => fn ($query) => $query->where('type', 'dislike'),
            ])
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();

        $maxViews = max(1, $topArticles->max('views_count'));

        return view('admin.blog-stats.index', compact(
            'totalViews',
            'totalLikes',
            'totalDislikes',
            'topArticles',
            'maxViews'
        ));
    }
}

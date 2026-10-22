<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleReactionController extends Controller
{
    public function __invoke(Request $request, Article $article): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:like,dislike'],
        ]);

        $sessionId = $request->session()->getId();
        $existing = ArticleReaction::where('article_id', $article->id)->where('session_id', $sessionId)->first();

        if ($existing && $existing->type === $data['type']) {
            $existing->delete();
            $userReaction = null;
        } elseif ($existing) {
            $existing->update(['type' => $data['type']]);
            $userReaction = $data['type'];
        } else {
            ArticleReaction::create(['article_id' => $article->id, 'session_id' => $sessionId, 'type' => $data['type']]);
            $userReaction = $data['type'];
        }

        return response()->json([
            'likes' => $article->reactions()->where('type', 'like')->count(),
            'dislikes' => $article->reactions()->where('type', 'dislike')->count(),
            'user_reaction' => $userReaction,
        ]);
    }
}

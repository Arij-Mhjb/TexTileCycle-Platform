<?php

namespace App\Http\Controllers;

use App\Models\ChatbotQueryLog;
use App\Models\ChatbotQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatWidgetController extends Controller
{
    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:500'],
            'session_id' => ['nullable', 'string', 'max:100'],
        ]);

        $match = ChatbotQuestion::findBestMatch($data['message']);

        ChatbotQueryLog::create([
            'question_text' => $data['message'],
            'matched_question_id' => $match?->id,
            'session_id' => $data['session_id'] ?? null,
        ]);

        if (! $match) {
            return response()->json([
                'matched' => false,
                'answer' => "Je n'ai pas encore de réponse précise à cette question. Notre équipe l'examinera prochainement — vous pouvez nous contacter directement pour une réponse personnalisée.",
                'suggestions' => ChatbotQuestion::query()->where('is_active', true)->inRandomOrder()->limit(3)->get(['id', 'question']),
            ]);
        }

        $suggestions = ChatbotQuestion::query()->where('is_active', true)
            ->where('chatbot_category_id', $match->chatbot_category_id)
            ->where('id', '!=', $match->id)
            ->limit(3)
            ->get(['id', 'question']);

        return response()->json([
            'matched' => true,
            'answer' => $match->answer,
            'suggestions' => $suggestions,
        ]);
    }

    public function suggestions(): JsonResponse
    {
        $popular = ChatbotQuestion::query()->where('is_active', true)
            ->withCount('logs')
            ->orderByDesc('logs_count')
            ->limit(5)
            ->get(['id', 'question']);

        if ($popular->every(fn ($question) => $question->logs_count === 0)) {
            $popular = ChatbotQuestion::query()->where('is_active', true)->orderBy('position')->limit(5)->get(['id', 'question']);
        }

        return response()->json(['suggestions' => $popular]);
    }
}

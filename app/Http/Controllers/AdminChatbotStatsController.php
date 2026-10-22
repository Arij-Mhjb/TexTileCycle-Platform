<?php

namespace App\Http\Controllers;

use App\Models\ChatbotQueryLog;
use App\Models\ChatbotQuestion;
use Illuminate\View\View;

class AdminChatbotStatsController extends Controller
{
    public function __invoke(): View
    {
        $totalQuestions = ChatbotQuestion::count();
        $totalConversations = ChatbotQueryLog::count();
        $matchedConversations = ChatbotQueryLog::whereNotNull('matched_question_id')->count();
        $matchRate = $totalConversations ? round($matchedConversations / $totalConversations * 100, 1) : 0;

        $topQuestions = ChatbotQuestion::withCount('logs')->orderByDesc('logs_count')->limit(10)->get();

        $unmatched = ChatbotQueryLog::whereNull('matched_question_id')
            ->selectRaw('question_text, count(*) as total')
            ->groupBy('question_text')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $days = collect(range(13, 0))->map(fn ($offset) => now()->subDays($offset)->startOfDay());
        $volume = $days->map(fn ($day) => [
            'label' => $day->format('d/m'),
            'count' => ChatbotQueryLog::whereDate('created_at', $day)->count(),
        ]);
        $maxVolume = max(1, $volume->max('count'));

        return view('admin.chatbot-stats.index', compact(
            'totalQuestions',
            'totalConversations',
            'matchRate',
            'topQuestions',
            'unmatched',
            'volume',
            'maxVolume'
        ));
    }
}

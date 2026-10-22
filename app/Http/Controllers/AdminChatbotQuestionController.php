<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatbotQuestionRequest;
use App\Http\Requests\UpdateChatbotQuestionRequest;
use App\Models\ChatbotCategory;
use App\Models\ChatbotQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminChatbotQuestionController extends Controller
{
    public function index(Request $request): View
    {
        $query = ChatbotQuestion::with('category')->withCount('logs');

        if ($request->filled('search')) {
            $query->where('question', 'like', '%' . $request->string('search') . '%');
        }

        $items = $query->orderBy('position')->latest()->paginate(10)->withQueryString();

        return view('admin.chatbot-questions.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.chatbot-questions.create', [
            'item' => new ChatbotQuestion,
            'categories' => ChatbotCategory::orderBy('name')->get(),
        ]);
    }

    public function store(StoreChatbotQuestionRequest $request): RedirectResponse
    {
        ChatbotQuestion::create($request->validated());

        return to_route('admin.chatbot-questions.index')->with('success', 'Question ajoutée.');
    }

    public function edit(ChatbotQuestion $chatbotQuestion): View
    {
        return view('admin.chatbot-questions.edit', [
            'item' => $chatbotQuestion,
            'categories' => ChatbotCategory::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateChatbotQuestionRequest $request, ChatbotQuestion $chatbotQuestion): RedirectResponse
    {
        $chatbotQuestion->update($request->validated());

        return to_route('admin.chatbot-questions.index')->with('success', 'Question mise à jour.');
    }

    public function destroy(ChatbotQuestion $chatbotQuestion): RedirectResponse
    {
        $chatbotQuestion->delete();

        return back()->with('success', 'Question supprimée.');
    }
}

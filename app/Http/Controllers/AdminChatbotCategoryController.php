<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatbotCategoryRequest;
use App\Http\Requests\UpdateChatbotCategoryRequest;
use App\Models\ChatbotCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminChatbotCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = ChatbotCategory::withCount('questions');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->string('search') . '%');
        }

        $items = $query->latest()->paginate(10)->withQueryString();

        return view('admin.chatbot-categories.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.chatbot-categories.create', ['item' => new ChatbotCategory]);
    }

    public function store(StoreChatbotCategoryRequest $request): RedirectResponse
    {
        ChatbotCategory::create($request->validated());

        return to_route('admin.chatbot-categories.index')->with('success', 'Catégorie créée.');
    }

    public function edit(ChatbotCategory $chatbotCategory): View
    {
        return view('admin.chatbot-categories.edit', ['item' => $chatbotCategory]);
    }

    public function update(UpdateChatbotCategoryRequest $request, ChatbotCategory $chatbotCategory): RedirectResponse
    {
        $chatbotCategory->update($request->validated());

        return to_route('admin.chatbot-categories.index')->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(ChatbotCategory $chatbotCategory): RedirectResponse
    {
        $chatbotCategory->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }
}

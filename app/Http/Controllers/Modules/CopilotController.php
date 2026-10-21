<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Services\Ai\Copilot\CopilotService;
use App\Services\Ai\Copilot\CopilotTools;
use App\Services\Ai\HuggingFaceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Copilote IA de la marque : questions en langage naturel sur les fournisseurs, l'impact et le recyclage. */
class CopilotController extends Controller
{
    public function index(Request $request, CopilotTools $tools): View
    {
        return view('modules.copilot', [
            'brand' => $request->user()->brand()->firstOrFail(),
            'toolLabels' => CopilotTools::LABELS,
            'model' => app(HuggingFaceClient::class)->enabled() ? (config('huggingface.chat_models')[0] ?? null) : null,
        ]);
    }

    public function ask(Request $request, CopilotService $copilot): JsonResponse
    {
        $data = $request->validate([
            'question' => 'required|string|min:2|max:1000',
            'history' => 'nullable|array|max:12',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:4000',
        ]);

        return response()->json($copilot->ask($request->user()->brand()->firstOrFail(), $data['question'], $data['history'] ?? []));
    }
}

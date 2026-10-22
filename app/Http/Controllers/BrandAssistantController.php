<?php

namespace App\Http\Controllers;

use App\Services\BrandPlatformAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandAssistantController extends Controller
{
    public function __invoke(Request $request, BrandPlatformAssistant $assistant): JsonResponse
    {
        $data = $request->validate([
            'question' => 'required|string|min:2|max:1000', 'page' => 'nullable|string|max:500',
            'history' => 'nullable|array|max:6', 'history.*.role' => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:1500',
        ]);

        return response()->json($assistant->answer($request->user()->brand, $data['question'], $data['page'] ?? '', $data['history'] ?? []));
    }
}

<?php

return [
    'name' => env('BRAND_ASSISTANT_NAME', 'TexTileGuide'),
    'key' => env('BRAND_ASSISTANT_API_KEY', env('BRAND_AI_API_KEY')),
    // Accepte aussi l’identifiant d’un modèle OpenAI fine-tuné appartenant au projet.
    'model' => env('BRAND_ASSISTANT_MODEL', env('BRAND_AI_MODEL', 'gpt-4o-mini')),
    'knowledge_path' => resource_path('ai/brand-assistant-knowledge.md'),
];

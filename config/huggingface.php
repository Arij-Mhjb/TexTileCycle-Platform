<?php

/*
| Hugging Face Inference (router.huggingface.co) — modèles open source utilisés par
| la Seconde vie de la matière (analyse des photos de défauts lors d'un retour fournisseur).
| Jeton gratuit : https://huggingface.co/settings/tokens (type « Fine-grained » avec le preset « Inference »).
| Sans jeton, l’analyse passe par Gemini (si configuré), sinon la déclaration de la marque fait foi.
*/

return [
    'enabled' => env('HF_AI_ENABLED', true),
    'token' => env('HF_TOKEN'),
    'base_url' => env('HF_BASE_URL', 'https://router.huggingface.co/hf-inference/models'),
    'timeout' => env('HF_TIMEOUT', 30),

    // Classification d'image « zero-shot » : le modèle compare la photo à des libellés en texte.
    'vision_model' => env('HF_VISION_MODEL', 'openai/clip-vit-large-patch14'),

];

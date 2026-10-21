<?php

/*
| Hugging Face (router.huggingface.co) — modèles open source utilisés dans les modules d'Ons :
|  - Fournisseurs : lecture des certificats par un modèle vision-langage (Qwen2.5-VL) ;
|  - Impact ESG   : détection des allégations écologiques vagues (ClimateBERT, mDeBERTa zero-shot) ;
|  - Recyclage    : défauts et fibre reconnus sur photo (CLIP) ;
|  - Copilote IA  : LLM avec appels d'outils sur les données de la marque.
| Jeton gratuit : https://huggingface.co/settings/tokens (type « Fine-grained », cocher
| « Make calls to Inference Providers »). Sans jeton, chaque fonction passe en mode local.
*/

$list = fn (string $value) => array_values(array_filter(array_map('trim', explode(',', $value))));

return [
    'enabled' => env('HF_AI_ENABLED', true),
    'token' => env('HF_TOKEN'),
    'base_url' => env('HF_BASE_URL', 'https://router.huggingface.co/hf-inference/models'),
    'chat_url' => env('HF_CHAT_URL', 'https://router.huggingface.co/v1/chat/completions'),
    'timeout' => env('HF_TIMEOUT', 30),
    'chat_timeout' => env('HF_CHAT_TIMEOUT', 60),

    // Classification d'image « zero-shot » (CLIP) : défauts et nature de la fibre.
    'vision_model' => env('HF_VISION_MODEL', 'openai/clip-vit-large-patch14'),

    // Modèles vision-langage (lecture de documents), essayés dans l'ordre.
    'vlm_models' => $list(env('HF_VLM_MODELS', 'Qwen/Qwen2.5-VL-7B-Instruct,zai-org/GLM-4.5V,Qwen/Qwen2.5-VL-3B-Instruct')),

    // LLM avec appels d'outils (function calling) pour le copilote, essayés dans l'ordre.
    'chat_models' => $list(env('HF_CHAT_MODELS', 'openai/gpt-oss-120b,Qwen/Qwen2.5-72B-Instruct,meta-llama/Llama-3.3-70B-Instruct')),

    // Allégations environnementales : ClimateBERT (anglais) et zero-shot multilingue (français).
    'claims_model' => env('HF_CLAIMS_MODEL', 'climatebert/environmental-claims'),
    'specificity_model' => env('HF_SPECIFICITY_MODEL', 'climatebert/distilroberta-base-climate-specificity'),
    'zero_shot_model' => env('HF_ZERO_SHOT_MODEL', 'MoritzLaurer/mDeBERTa-v3-base-mnli-xnli'),
];

<?php

return [
    'key' => env('PRODUCT_LABEL_AI_API_KEY', env('BRAND_AI_API_KEY')),
    'model' => env('PRODUCT_LABEL_AI_MODEL', env('BRAND_AI_MODEL', 'gpt-4o-mini')),
];

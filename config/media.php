<?php

return [
    'product_storage' => env(
        'PRODUCT_MEDIA_STORAGE',
        env('APP_ENV') === 'production' ? 'cloudinary' : 'public'
    ),
];

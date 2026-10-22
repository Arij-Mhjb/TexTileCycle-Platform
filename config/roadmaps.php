<?php

return ['proof_storage' => env('ROADMAP_PROOF_STORAGE', env('APP_ENV') === 'production' ? 'cloudinary' : 'local')];

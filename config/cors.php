<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_origins' => ['http://localhost:5173'], // l'URL de ton React en dev
    'supports_credentials' => true,
];

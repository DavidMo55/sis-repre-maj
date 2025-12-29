<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí se configura el comportamiento de CORS. El Frontend (Vue) debe 
    | estar en la lista de orígenes permitidos (allowed_origins)
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'search/*', 'pedidos/*'],  

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5174', 
        'http://127.0.0.1:5174', 
        
        'http://localhost:5173', 
        'http://127.0.0.1:5173',
        
        'http://localhost:8000', 
        'http://127.0.0.1:8000',
        
        'https://mestockexterno.com',
        'http://mestockexterno.com',
    ],

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
    'allowed_origins' => ['https://mestockexterno.com'],
    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
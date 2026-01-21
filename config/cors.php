<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí se configura el comportamiento de CORS. El Frontend (Vue) debe 
    | estar en la lista de orígenes permitidos.
    |
    */

    // Se incluyen todos los prefijos de ruta que utiliza tu aplicación
    'paths' => [
        'api/*', 
        'sanctum/csrf-cookie', 
        'login', 
        'logout', 
        'search/*', 
        'pedidos/*',
        'proxy/*'
    ],

    'allowed_methods' => ['*'],

    // UNIFICAMOS todos los orígenes en una sola lista
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

    // Cabeceras permitidas para que el Token Bearer viaje sin problemas
    'allowed_headers' => [
        'Content-Type', 
        'Authorization', 
        'X-Requested-With', 
        'Accept',
        'X-XSRF-TOKEN'
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    // IMPORTANTE: Debe ser true si usas Sanctum o manejas sesiones con cookies
    'supports_credentials' => true,

];
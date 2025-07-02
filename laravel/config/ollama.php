<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ollama Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para integração com Ollama AI
    |
    */

    'url' => env('OLLAMA_URL', 'http://localhost:11434'),
    'model' => env('OLLAMA_MODEL', 'llama3'),
    'timeout' => env('OLLAMA_TIMEOUT', 60),
    'max_context' => env('MCP_MAX_CONTEXT', 4000),
    'rate_limit' => env('MCP_RATE_LIMIT', 100),
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    
    'cache' => [
        'enabled' => env('OLLAMA_CACHE_ENABLED', true),
        'ttl' => env('OLLAMA_CACHE_TTL', 3600), // 1 hora
        'prefix' => 'ollama_cache_',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Model Settings
    |--------------------------------------------------------------------------
    */
    
    'models' => [
        'llama3' => [
            'name' => 'llama3',
            'context_length' => 4096,
            'description' => 'Modelo geral recomendado para início'
        ],
        'llama3:70b' => [
            'name' => 'llama3:70b',
            'context_length' => 8192,
            'description' => 'Maior qualidade, mais recursos necessários'
        ],
        'mistral' => [
            'name' => 'mistral',
            'context_length' => 4096,
            'description' => 'Alternativa eficiente'
        ],
        'codellama' => [
            'name' => 'codellama',
            'context_length' => 4096,
            'description' => 'Especializado em código'
        ],
    ],
];

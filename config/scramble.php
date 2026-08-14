<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => 'api',
    'api_domain' => null,
    'export_path' => 'docs/openapi.json',
    'cache' => [
        'key' => 'scramble.openapi',
        'store' => 'file',
    ],
    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => <<<'MD'
API REST do **GynTraining** — gerenciamento e acompanhamento de treinos de academia.

## Autenticação
Use Laravel Sanctum com header `Authorization: Bearer {token}`.

Obtenha o token em `POST /api/v1/auth/login` ou `POST /api/v1/auth/register`.

## Formato de resposta

```json
{
  "success": true,
  "message": "Operação realizada com sucesso",
  "data": {},
  "meta": { "current_page": 1, "per_page": 20, "total": 100 }
}
```

## Papéis (RBAC)
`admin`, `gym_admin`, `trainer`, `student`
MD,
    ],
    'ui' => [
        'title' => 'GynTraining API',
    ],
    'renderer' => 'elements',
    'renderers' => [
        'elements' => [
            'view' => 'scramble::docs',
            'theme' => 'light',
            'hideTryIt' => false,
            'hideSchemas' => false,
            'logo' => '',
            'tryItCredentialsPolicy' => 'include',
            'layout' => 'responsive',
            'router' => 'hash',
        ],
        'scalar' => [
            'view' => 'scramble::scalar',
            'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',
            'theme' => 'laravel',
            'proxyUrl' => 'https://proxy.scalar.com',
            'darkMode' => false,
            'showDeveloperTools' => 'never',
            'agent' => ['disabled' => true],
            'credentials' => 'include',
        ],
    ],
    'servers' => null,
    'enum_cases_description_strategy' => 'description',
    'enum_cases_names_strategy' => false,
    'flatten_deep_query_parameters' => true,
    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],
    'extensions' => [],
    'security_strategy' => [
        \Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy::class,
        [
            'middleware' => ['auth', 'auth:*'],
            'scheme' => \Dedoc\Scramble\Support\Generator\SecurityScheme::http('bearer', 'Sanctum'),
        ],
    ],
];

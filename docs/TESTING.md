# Testes — GynTraining

## Executar

```bash
make test
# ou
docker compose exec app php artisan test
```

Com cobertura de filtro:

```bash
docker compose exec app php artisan test --filter=SecurityTest
docker compose exec app php artisan test tests/Feature/Api/V1/IntegrationFlowTest.php
```

## Estrutura

```
tests/
├── Feature/
│   ├── Api/V1/
│   │   ├── Auth/           # Login, registro, reset, policies
│   │   ├── ApiDocumentationTest.php
│   │   ├── CatalogTest.php
│   │   ├── DashboardTest.php
│   │   ├── IntegrationFlowTest.php   # Fluxo completo ponta a ponta
│   │   ├── NotificationTest.php
│   │   ├── ProgressTest.php
│   │   ├── SecurityTest.php          # Autorização e isolamento
│   │   ├── SoftDeleteTest.php
│   │   ├── WorkoutExecutionTest.php
│   │   └── WorkoutTest.php
│   └── ExampleTest.php
└── Unit/
    ├── Services/           # Lógica de negócio isolada
    └── SoftDeleteConventionTest.php
```

## O que é testado

| Área | Arquivo(s) |
|------|------------|
| Autenticação Sanctum | `Auth/AuthTest.php` |
| Permissões / RBAC | `Auth/UserPolicyTest.php`, `SecurityTest.php` |
| Cadastros (gyms, alunos, exercícios) | `CatalogTest.php` |
| Fichas de treino | `WorkoutTest.php` |
| Execução (start, log, finish) | `WorkoutExecutionTest.php` |
| Evolução (medidas, metas, progresso) | `ProgressTest.php` |
| Dashboards | `DashboardTest.php` |
| Notificações | `NotificationTest.php` |
| Soft delete | `SoftDeleteTest.php`, `SoftDeleteConventionTest.php` |
| Documentação OpenAPI | `ApiDocumentationTest.php` |
| Fluxo integrado | `IntegrationFlowTest.php` |
| Segurança (401/403, dados sensíveis) | `SecurityTest.php` |

## Ambiente de testes

- Banco: **SQLite in-memory** (`phpunit.xml`)
- Fila: **sync** (notificações processadas imediatamente)
- Mail: **array** (sem envio real)

## Convenções

- Seeders mínimos em `setUp()`: `RoleSeeder`, `PermissionSeeder` (+ catálogo quando necessário)
- Respostas API validadas com `assertJsonPath('success', true)` quando relevante
- Soft delete validado com `assertSoftDeleted()` — nunca `forceDelete` em entidades de negócio

## CI (GitHub Actions)

A cada push ou PR na branch `main`, o workflow `.github/workflows/ci.yml` executa:

1. `composer install`
2. `php artisan test` (SQLite in-memory)
3. `npm ci` + `npm run build`

Acompanhe em **Actions** no repositório GitHub.

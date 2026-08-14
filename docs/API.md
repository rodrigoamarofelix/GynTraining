# GynTraining — Documentação da API

Base URL: `http://localhost:8080/api/v1`

## Documentação interativa (Swagger / OpenAPI)

| Recurso | URL |
|---------|-----|
| **UI (Stoplight Elements)** | http://localhost:8080/docs/api |
| **Spec JSON** | http://localhost:8080/docs/api.json |
| **Arquivo exportado** | [openapi.json](./openapi.json) |

Para regenerar o arquivo OpenAPI:

```bash
make docs-export
# ou
docker compose exec app php artisan scramble:export --path=docs/openapi.json
```

> A UI interativa só fica disponível em ambiente `local` por padrão (segurança Scramble).

---

## Autenticação

Utiliza **Laravel Sanctum** (Bearer Token).

```http
Authorization: Bearer {seu_token}
Content-Type: application/json
Accept: application/json
```

### Obter token

```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"student@gyntraining.local","password":"password"}'
```

Resposta:

```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "user": { "id": 1, "name": "...", "email": "..." },
    "token": "1|abc..."
  }
}
```

---

## Formato padrão de resposta

### Sucesso

```json
{
  "success": true,
  "message": "Operação realizada com sucesso",
  "data": {}
}
```

### Sucesso com paginação

```json
{
  "success": true,
  "message": "Operação realizada com sucesso",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100
  }
}
```

### Erro de validação (422)

```json
{
  "success": false,
  "message": "Não foi possível realizar a operação",
  "errors": {
    "email": ["O campo email é obrigatório."]
  }
}
```

### Não autenticado (401) / Não autorizado (403)

```json
{
  "success": false,
  "message": "Não autenticado."
}
```

---

## Módulos da API

### Autenticação (`/auth`)
| Método | Endpoint | Auth |
|--------|----------|------|
| POST | `/auth/register` | Não |
| POST | `/auth/login` | Não |
| POST | `/auth/forgot-password` | Não |
| POST | `/auth/reset-password` | Não |
| POST | `/auth/logout` | Sim |
| GET | `/auth/me` | Sim |

### Cadastros
| Recurso | Prefixo |
|---------|---------|
| Academias | `/gyms` |
| Alunos | `/students` |
| Professores | `/trainers` |
| Grupos musculares | `/muscle-groups` |
| Categorias de exercício | `/exercise-categories` |
| Exercícios | `/exercises` |

### Treinos (fichas)
| Recurso | Prefixo |
|---------|---------|
| Fichas | `/workouts` |
| Dias | `/workout-days` |
| Exercícios da ficha | `/workout-exercises` |
| Séries planejadas | `/workout-sets` |
| Iniciar treino | `POST /workouts/{id}/start` |
| Finalizar treino | `POST /workouts/{id}/finish` |

### Execução
| Recurso | Prefixo |
|---------|---------|
| Sessões | `/workout-sessions` |
| Histórico de séries | `/history` |

### Evolução
| Recurso | Prefixo |
|---------|---------|
| Resumo de progresso | `/progress` |
| Medidas corporais | `/body-measurements` |
| Fotos de evolução | `/progress-photos` |
| Metas | `/goals` |
| Dashboard | `/dashboard` |

### Notificações
| Método | Endpoint |
|--------|----------|
| GET | `/notifications` |
| GET | `/notifications/unread-count` |
| POST | `/notifications/{id}/read` |
| POST | `/notifications/read-all` |
| GET/PUT | `/notification-preferences` |

---

## Papéis e permissões

| Papel | Slug | Descrição |
|-------|------|-----------|
| Administrador | `admin` | Acesso total |
| Admin da academia | `gym_admin` | Gerencia academia |
| Professor | `trainer` | Gerencia fichas e alunos |
| Aluno | `student` | Executa treinos, vê evolução |

Permissões: `users.manage`, `gyms.manage`, `exercises.manage`, `workouts.manage`, `workouts.execute`, `progress.view`

---

## Rate limiting

- Login: **5 tentativas/minuto** por e-mail + IP

---

## Versionamento

Todas as rotas usam prefixo `/api/v1/`. Futuras versões usarão `/api/v2/` sem quebrar a v1.

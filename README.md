# GynTraining

[![CI](https://github.com/rodrigoamarofelix/GynTraining/actions/workflows/ci.yml/badge.svg)](https://github.com/rodrigoamarofelix/GynTraining/actions/workflows/ci.yml)

Sistema web e mobile de gerenciamento e acompanhamento de treinos de academia.

## Stack

| Componente | Versao |
|---|---|
| PHP | 8.4 |
| Laravel | 13.x |
| PostgreSQL | 17 |
| Redis | 7 |
| Nginx | 1.27 |
| Mailpit | latest |
| Docker Compose | v2 |

## Pre-requisitos

- Docker Engine 24+
- Docker Compose v2
- Make (opcional, recomendado)
- Git

## Instalacao rapida

```bash
cd GynTraining
cp .env.example .env
make setup
docker compose exec app php artisan key:generate
```

Acesse:

- **Aplicacao Web:** http://localhost:8080
- Health check: http://localhost:8080/up
- **API Docs (Swagger):** http://localhost:8080/docs/api
- Mailpit: http://localhost:8025

## Comandos Make

Ver Makefile ou execute `make help`.

## Testes

Ver [docs/TESTING.md](docs/TESTING.md). Suite atual: **66+ testes** cobrindo auth, RBAC, cadastros, treinos, execução, evolução, dashboards, notificações, segurança e frontend.

```bash
make test
```

## Frontend

Ver [docs/FRONTEND.md](docs/FRONTEND.md). SPA Vue 3 integrada ao Laravel via Vite.

```bash
make frontend-build   # compilar assets
npm run dev           # desenvolvimento com HMR (Node 20+)
```

### Usuários demo

Após `make seed`, use em http://localhost:8080/login (senha de todos: **`password`**):

| Papel | E-mail |
|---|---|
| Aluno | `student@gyntraining.local` |
| Professor | `trainer@gyntraining.local` |
| Admin da academia | `gymadmin@gyntraining.local` |
| Admin global | `admin@gyntraining.local` |

Se não existir nenhum usuário: `make seed` ou `make fresh`.

## Roadmap

- [x] Fase 1 - Infraestrutura
- [x] Fase 2 - Autenticacao
- [x] Fase 3 - Cadastros
- [x] Fase 4 - Treinos
- [x] Fase 5 - Execucao
- [x] Fase 6 - Evolucao
- [x] Fase 7 - Dashboards
- [x] Fase 8 - Notificacoes
- [x] Fase 9 - Documentacao API
- [x] Fase 10 - Testes
- [x] Fase 11 - Frontend Web
- [x] Fase 12 - Frontend Professor e Admin
- [x] Fase 13 - Admin academia, categorias, cadastro pendente, polish aluno/professor

### Proximos passos

- [ ] App mobile (Flutter/React Native)
- [ ] Graficos de evolucao e relatorios
- [x] CI (GitHub Actions)
- [ ] Deploy producao

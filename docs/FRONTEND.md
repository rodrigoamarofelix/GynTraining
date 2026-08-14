# Frontend Web — GynTraining

Interface web responsiva consumindo a API REST (`/api/v1`).

## Stack

| Tecnologia | Uso |
|---|---|
| Vue 3 | SPA e componentes reativos |
| Vue Router | Navegação client-side |
| Pinia | Estado global (auth) |
| Axios | Cliente HTTP |
| Tailwind CSS 4 | Estilos utilitários |
| Vite 8 | Build e HMR |

## Estrutura

```
resources/js/
├── api/client.js          # Axios + interceptors
├── stores/auth.js         # Login, registro, sessão
├── router/index.js        # Rotas e guards
├── layouts/               # AppLayout, GuestLayout
├── components/            # UI reutilizável, RestTimer, AppNav
└── pages/                 # Telas por módulo
```

## Telas

| Rota | Tela |
|---|---|
| `/login` | Login |
| `/register` | Cadastro (aluno) |
| `/` | Dashboard (por papel) |
| `/treino` | Minha ficha |
| `/treino/executar/{plan}/{day}` | Execução + cronômetro |
| `/historico` | Histórico de cargas |
| `/evolucao` | Resumo de evolução |
| `/medidas` | Medidas corporais |
| `/metas` | Metas |
| `/fotos` | Fotos de evolução |
| `/exercicios` | Catálogo |
| `/notificacoes` | Notificações |
| `/perfil` | Perfil |
| `/professor` | Painel do professor |
| `/admin` | Painel administrativo |

## Autenticação

O frontend usa **Bearer token** (Sanctum) armazenado em `localStorage`. Todas as requisições autenticadas enviam:

```
Authorization: Bearer {token}
```

## Desenvolvimento

Requer **Node.js 20+** (Vite 8).

```bash
# Instalar dependências
npm install

# Dev server (HMR) — em paralelo ao Docker
npm run dev

# Build produção
npm run build
# ou via Make (usa container Node 22):
make frontend-build
```

Com Docker rodando, acesse: http://localhost:8080

## Usuários demo (após `make seed`)

Todos usam a senha **`password`**:

| Papel | E-mail | Nome |
|---|---|---|
| Aluno | `student@gyntraining.local` | Maria Aluna |
| Professor | `trainer@gyntraining.local` | Carlos Personal |
| Admin | `admin@gyntraining.local` | Administrador |

Se a lista estiver vazia, rode:

```bash
make seed
# ou recrie tudo:
make fresh
```

## Design

- Tema escuro mobile-first
- Cards, badges e botões grandes para uso em academia
- Cronômetro de descanso com alerta sonoro e vibração (quando suportado)

## PWA (instalável)

Após `npm run build`, o app pode ser instalado na tela inicial (Chrome/Edge desktop ou mobile).

**Como testar no PC (sem Wi‑Fi):**

1. `make frontend-build` ou `npm run build`
2. Abra http://localhost:8080
3. Chrome → F12 → **Application** → **Manifest** e **Service Workers**
4. Opcional: Lighthouse → audit **PWA**
5. Ícone de instalar na barra de endereço do Chrome

Arquivos principais: `vite.config.js` (plugin PWA), `public/icons/`, `public/build/manifest.webmanifest`, `public/build/sw.js`.

## Fase 12 — Professor e Admin

### Professor (`trainer@gyntraining.local`)

| Rota | Função |
|---|---|
| `/professor` | Dashboard + atalhos |
| `/professor/alunos` | Listar alunos |
| `/professor/alunos/:id` | Fichas do aluno |
| `/professor/fichas/nova` | Criar ficha (dias, exercícios, séries) |
| `/professor/fichas/:id` | Ver/editar ficha |

### Admin (`admin@gyntraining.local`)

| Rota | Função |
|---|---|
| `/admin` | Dashboard + atalhos |
| `/admin/academias` | CRUD academias |
| `/admin/exercicios` | Cadastrar exercícios |
| `/admin/grupos` | Cadastrar grupos musculares |

### Aluno — upload de fotos

Em `/fotos`, alunos podem enviar fotos de evolução (frente/costas/lado) diretamente pelo navegador.

# EcoDescarte ♻️

Protótipo de mapa colaborativo de pontos de descarte correto de resíduos em todo o **território brasileiro**.

Qualquer pessoa pode consultar no mapa onde descartar cada tipo de resíduo (pilhas, óleo de cozinha, eletrônicos, lâmpadas, entulho, medicamentos etc.) e sugerir novos pontos de coleta. As sugestões ficam pendentes até serem aprovadas pela equipe na área administrativa.

## Funcionalidades

- **Mapa público** (`/`) — pontos de coleta aprovados exibidos em um mapa (Leaflet + OpenStreetMap), com filtro por tipo de resíduo.
- **Sugestão de pontos** — qualquer visitante pode cadastrar um novo ponto, que entra como *pendente*.
- **Área da equipe** (`/admin`) — login com usuário/senha, listagem dos pontos pendentes e aprovados, com ações de aprovar, editar e excluir.
- **Base de dados real** — o seeder carrega uma base inicial de pontos de coleta reais (Itajaí e região, SC), que cresce com as contribuições dos usuários de qualquer lugar do país.

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.3+, Laravel 13 (API REST) |
| Frontend | Vue 3 + Vuetify 3 + Vue Router (SPA), Vite |
| Mapa | Leaflet + OpenStreetMap |
| Banco | SQLite (`database/database.sqlite`) |

O Laravel serve a SPA por uma única view Blade; todo o roteamento de tela acontece no cliente. A comunicação é via API JSON em `/api` (rotas públicas para consulta/sugestão de pontos, rotas protegidas por token Bearer para a administração).

## Como rodar

Pré-requisitos: **PHP 8.3+**, **Composer** e **Node.js 20+**.

```bash
composer run setup      # instala dependências, cria .env, gera key, migra e builda os assets
php artisan db:seed     # cria o usuário da equipe e carrega os pontos de coleta
composer run dev        # sobe servidor, fila, logs e Vite de uma vez
```

Depois é só abrir **http://localhost:8000**.

> Se a porta 8000 estiver ocupada, use `php artisan serve --port=8001` (junto com `npm run dev` em outro terminal) e acesse pelo endereço `localhost`, não `127.0.0.1`.

Se o `migrate` reclamar que o banco não existe, crie o arquivo antes: `touch database/database.sqlite`.

## Acesso à área da equipe

Acesse **/admin** e entre com as credenciais de demonstração:

- **Usuário:** `admin`
- **Senha:** `admin`

> Credenciais fixas apenas para fins de avaliação do protótipo.

## Estrutura resumida

```
app/
  Http/Controllers/        # API pública (pontos, login) e Admin (aprovação/CRUD)
  Models/                  # CollectionPoint, User
database/
  migrations/              # tabela de pontos de coleta + colunas de auth
  seeders/                 # usuário admin + pontos reais de Itajaí/região
resources/js/
  App.vue, router.js       # casca da SPA e rotas (/ e /admin)
  views/MapView.vue        # mapa público com filtros por tipo de resíduo
  views/AdminView.vue      # painel da equipe
  components/PointFormDialog.vue
  wasteTypes.js            # catálogo dos tipos de resíduo (ícones e cores)
routes/
  api.php                  # rotas da API (públicas e protegidas por token)
  web.php                  # fallback que entrega a SPA
```

## Testes

```bash
composer run test
```

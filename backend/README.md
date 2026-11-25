# ELOMOB · API (Laravel)

API REST do ELOMOB: rede de transporte do Recife, planejador de trajetos com
acessibilidade e integração tarifária, contas e favoritos. Banco PostgreSQL 16+
com PostGIS; autenticação por token com Laravel Sanctum.

## Requisitos

- PHP 8.3+ com as extensões `pdo_pgsql`, `mbstring`, `openssl`, `intl`, `fileinfo` e `zip`
- Composer 2
- PostgreSQL 16+ com PostGIS 3

## Primeira execução

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

No `.env`, aponte para o PostgreSQL:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=elomob
DB_USERNAME=elomob
DB_PASSWORD=
```

Criar a extensão PostGIS exige um superusuário. Faça isso uma vez, como `postgres`,
depois de criar o banco:

```bash
psql -U postgres -d elomob -c "CREATE EXTENSION IF NOT EXISTS postgis;"
```

Depois rode as migrations com a carga demonstrativa, que vem de
`../database/sql/02_seed.sql`:

```bash
php artisan migrate --seed
php artisan serve
```

`GET http://127.0.0.1:8000/api/health` confirma a conexão com o banco e o PostGIS.

## Endpoints

| Método | Rota | Descrição |
| --- | --- | --- |
| GET | `/api/health` | Conexão com o banco, versão do PostGIS e tamanho da rede |
| GET | `/api/transport-modes` | Modais |
| GET | `/api/stops` | Paradas; `?lat=&lng=&radius=` ordena por distância (PostGIS), `?q=` filtra pelo nome, `?mode=` pelo modal |
| GET | `/api/stops/{id}` | Parada com as linhas que a atendem |
| GET | `/api/lines` | Linhas; `?mode=` filtra pelo modal |
| GET | `/api/lines/{id}` | Linha com as paradas atendidas |
| GET | `/api/network` | Rede inteira em GeoJSON para o mapa |
| POST | `/api/trips` | Planeja um trajeto (com ou sem conta) |
| GET | `/api/trips/{id}` | Reabre uma busca (conta dona ou cabeçalho `X-Guest-Token`) |
| POST | `/api/auth/register` | Cria conta e devolve o token |
| POST | `/api/auth/login` | Devolve o token |
| POST | `/api/auth/logout` | Revoga o token atual 🔒 |
| GET · PATCH · DELETE | `/api/me` | Perfil, preferências e exclusão da conta 🔒 |
| GET | `/api/trips` | Histórico de buscas da conta 🔒 |
| GET · POST · DELETE | `/api/saved-places` | Favoritos 🔒 |

🔒 exige `Authorization: Bearer <token>`.

### Planejar um trajeto

```http
POST /api/trips
Content-Type: application/json

{
  "origin_id": 1,
  "destination_id": 4,
  "departure_at": "2026-11-20T08:00:00-03:00",
  "modes": ["bus", "metro"],
  "accessibility_needs": ["wheelchair"]
}
```

`departure_at` vazio significa sair agora. Sem `modes` e `accessibility_needs`,
valem as preferências do perfil (com token) ou nenhuma restrição. A caminhada é
sempre permitida. A resposta traz um itinerário por critério (`fastest`,
`cheapest`, `transfers`, `sustainable`), cada um com as etapas, a espera, o tempo
de integração, a tarifa paga e a geometria GeoJSON de cada trecho. Sem conta, a
resposta inclui `guest_token`, que reabre a busca pelo cabeçalho `X-Guest-Token`.

## Como o planejador funciona

`App\Services\Planning\RoutePlanner` roda Dijkstra sobre a rede carregada por
`NetworkRepository`, que a guarda no cache sob a versão de `network_versions`.
Gatilhos no banco renovam essa versão a cada mudança na rede. O estado da busca
é a parada, a linha atual, o modal anterior e a maior tarifa paga na cadeia de
integrações, o que mantém exata a regra de pagar só a diferença.

- Seguir na mesma linha não tem espera nem nova tarifa.
- Embarcar espera metade da frequência da linha, ou até o início do serviço; depois do fim do serviço a linha não é usada.
- Uma integração soma seu tempo de troca; com `fare_integrated`, cobra só a diferença até a maior tarifa da cadeia.
- Chegar a pé ou trocar sem integração tarifária cobra a tarifa cheia.
- As necessidades de acessibilidade filtram paradas de embarque, desembarque e caminhada, trechos e integrações (ver `docs/BANCO_DE_DADOS.md`).

Buscas sem conta são apagadas uma semana depois pelo agendamento diário de
`model:prune` (`php artisan schedule:work` em desenvolvimento).

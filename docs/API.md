# API do ELOMOB

A API REST do ELOMOB é feita em Laravel e responde em JSON no prefixo `/api/v1`. São 19 endpoints, que atendem todas as telas: rede de transporte, busca de trajetos, conta, perfil, favoritos e histórico. Cada um lê ou grava no PostgreSQL pelos models Eloquent; o catálogo da rede também fica 30 segundos no Redis. A organização do código está em [Arquitetura e escopo](ARQUITETURA.md), e as tabelas, em [Modelagem lógica](BANCO_DE_DADOS.md).

## Endereços

| Uso | Endereço |
| --- | --- |
| Pelo frontend, que encaminha a API na mesma origem | http://localhost:8000/api/v1 |
| Direto no Laravel | http://localhost:8002/api/v1 |

## Como chamar

- Envie `Accept: application/json` em toda requisição e `Content-Type: application/json` quando houver corpo.
- O login é por sessão, guardada em cookie. O cliente precisa devolver os cookies que recebe: o navegador faz isso sozinho; no curl, use `-c` e `-b`.
- `POST`, `PUT` e `DELETE` exigem o token CSRF no cabeçalho `X-CSRF-TOKEN`. O token vem em `GET /sessao`. Cadastro, login, logout e exclusão da conta trocam o token e devolvem o novo; use sempre o último recebido.
- Dinheiro vem em centavos (`fare_cents`, `cost_cents`, `total_cost_cents`), distâncias em metros e tempos em minutos.
- Datas seguem ISO 8601. `created_at` vem em UTC e `departure_at` no horário do Recife (`-03:00`). Nos favoritos, `created_at` vem como `AAAA-MM-DD hh:mm:ss`, também em UTC.
- As respostas de `/catalogo`, `/paradas`, `/linhas`, `/buscas` e `/trajetos` trazem `"source": "demo"`, porque a rede é demonstrativa.
- As respostas ligadas à sessão ou à conta vêm com `Cache-Control: no-store`.

## Endpoints

Na coluna Login, "Dono" quer dizer que não é preciso entrar, mas só quem fez a busca a abre: a conta que buscou ou, para um visitante, a mesma sessão do navegador.

### Sessão e conta

| Método | Caminho | Login | O que faz | Sucesso |
| --- | --- | --- | --- | --- |
| GET | `/sessao` | Não | Mostra a conta conectada e o token CSRF | 200, `user` (perfil ou `null`) e `csrf_token` |
| POST | `/cadastrar` | Não | Cria a conta e já entra com ela | 201, igual a `/sessao` |
| POST | `/entrar` | Não | Entra com e-mail e senha e renova a sessão | 200, igual a `/sessao` |
| POST | `/sair` | Sim | Sai da conta e descarta a sessão | 200, `user` nulo e token novo |
| GET | `/perfil` | Sim | Mostra os dados e as preferências da conta | 200, perfil |
| PUT | `/perfil` | Sim | Altera nome, e-mail e preferências | 200, perfil atualizado |
| PUT | `/perfil/senha` | Sim | Troca a senha e encerra as outras sessões da conta | 200, perfil |
| DELETE | `/perfil` | Sim | Exclui a conta, os favoritos e as buscas, e sai | 200, igual a `/sair` |

### Rede de transporte

| Método | Caminho | Login | O que faz | Sucesso |
| --- | --- | --- | --- | --- |
| GET | `/catalogo` | Não | Toda a rede: `stops`, `modes`, `lines` e `integrations` | 200 |
| GET | `/paradas` | Não | Paradas filtradas por texto, modal ou proximidade | 200, lista de paradas |
| GET | `/linhas` | Não | Linhas filtradas por texto ou modal | 200, lista de linhas |

### Buscas e trajetos

| Método | Caminho | Login | O que faz | Sucesso |
| --- | --- | --- | --- | --- |
| POST | `/buscas` | Não | Calcula os trajetos e grava a busca | 201, busca com `itineraries` |
| GET | `/buscas/{id}` | Dono | Abre de novo uma busca e seus trajetos | 200, busca com `itineraries` |
| GET | `/trajetos/{id}` | Dono | Mostra um trajeto com etapas e GeoJSON para o mapa | 200, trajeto detalhado |

### Favoritos e histórico

| Método | Caminho | Login | O que faz | Sucesso |
| --- | --- | --- | --- | --- |
| GET | `/favoritos` | Sim | Lista os locais salvos, na ordem em que foram salvos | 200, lista de favoritos |
| POST | `/favoritos` | Sim | Salva uma parada com um nome | 201, favorito |
| DELETE | `/favoritos/{id}` | Sim | Exclui um local salvo | 200, `{"data": {"deleted": true}}` |
| GET | `/historico` | Sim | As 20 buscas mais recentes da conta | 200, buscas sem `itineraries` |
| DELETE | `/historico` | Sim | Apaga todas as buscas da conta, com trajetos e etapas; os favoritos ficam | 200, `{"data": {"deleted": 3}}`, com o número de buscas apagadas |

Fora do prefixo, `GET /up` é a verificação de saúde do Laravel. O Docker Compose a usa para saber quando a API está pronta.

## Entradas e respostas

### Conta e perfil

| Endpoint | Campos |
| --- | --- |
| `POST /cadastrar` | `name` (até 100 caracteres), `email` (até 255 e sem outra conta igual), `password` (mínimo de 8) e `password_confirmation`, todos obrigatórios |
| `POST /entrar` | `email` e `password`. Com dados errados, responde 422 com "E-mail ou senha incorretos." no campo `email` |
| `PUT /perfil/senha` | `current_password`, `password` (mínimo de 8) e `password_confirmation`, todos obrigatórios. Com a senha atual errada, responde 422 com "A senha atual está incorreta." |
| `DELETE /perfil` | `password`, obrigatório, com a senha da conta. Errada, responde 422 com "A senha está incorreta." |
| `PUT /perfil` | `name`, `email`, `preferred_route` e `transport_modes`, obrigatórios; `accessibility_needs`, lista opcional e sem repetição dos tipos de necessidade (`wheelchair`, `reduced_mobility`, `visual` e `hearing`), que fica vazia quando ausente |

O perfil tem `id`, `name`, `email`, `preferred_route`, `transport_modes` e `accessibility_needs`. Uma conta nova começa com `fastest`, os cinco modais e nenhum tipo de necessidade. A senha não muda pelo `PUT /perfil`, só pelo `PUT /perfil/senha`. Depois da troca, a sessão que trocou continua com o mesmo token, e as outras sessões da conta recebem 401 na próxima requisição. `PUT /perfil/senha` e `DELETE /perfil` aceitam 5 tentativas por minuto por conta.

### Códigos aceitos

| Campo | Valores |
| --- | --- |
| `preferred_route` e `criterion` | `fastest` (mais rápido), `cheapest` (mais barato), `transfers` (menos integrações) e `sustainable` (mais sustentável, entre os trajetos que levam no máximo 25% a mais que o mais rápido sem táxi nem aplicativo, ou que o mais rápido quando todos usam uma corrida) |
| `transport_modes` e `modes` | `bus`, `metro`, `bike`, `taxi` e `app`, ao menos um e sem repetir |
| `mode` nos filtros | Os cinco acima e `walk` |

A caminhada (`walk`) não é uma opção de busca, porque as conexões a pé sempre entram no cálculo.

### Paradas e linhas

`GET /paradas` e `GET /linhas` aceitam na query string:

- `q`: texto de até 120 caracteres, procurado no nome da parada ou no nome e código da linha, sem diferenciar maiúsculas;
- `mode`: código do modal.

`/paradas` também aceita `latitude` e `longitude`, sempre juntas, e `radius`, em metros, de 100 a 20000, com padrão de 1000. Com coordenadas, só voltam as paradas dentro do raio, da mais próxima para a mais distante, cada uma com `distance_meters`. A distância é calculada pelo PostGIS.

Uma parada tem `id`, `name`, `address`, `latitude`, `longitude`, `accessible` (acesso sem degraus), `tactile_paving` (piso tátil), `visual_signs` (painel de informações), `bikes_available`, `docks_available` (bicicletas e vagas, nulos onde não há bicicleta compartilhada), `mode`, `mode_name` e `color`. Uma linha tem `id`, `code`, `name`, `starts_at`, `ends_at`, `frequency_minutes`, `fare_cents`, `mode`, `mode_name` e `color`. Uma integração tem a parada (`stop_id`, `name`, `latitude`, `longitude`), os modais de origem e destino da troca (`from_mode`, `to_mode` e seus nomes), `transfer_minutes`, `accessible`, `fare_integrated` (a troca cobra só a diferença até a tarifa mais alta) e `description`.

### Buscas

`POST /buscas` recebe:

| Campo | Regra |
| --- | --- |
| `origin_id` | Obrigatório; id de uma parada, vindo de `/catalogo` ou `/paradas` |
| `destination_id` | Obrigatório; outra parada, diferente da origem |
| `modes` | Obrigatório; lista de modais, como em "Códigos aceitos" |
| `departure_at` | Opcional; data e hora da saída. Sem fuso, vale o horário do Recife. Sem o campo, a busca é para sair agora |

Com login, a busca usa os tipos de necessidade do perfil: só entram trajetos com os recursos que cada tipo exige (tabela em [BANCO_DE_DADOS.md](BANCO_DE_DADOS.md#acessibilidade)). Para visitantes, a lista fica vazia. O que foi usado fica gravado em `filters`, com `modes` e `accessibility_needs`.

A busca responde com `id`, origem e destino (ids e nomes), `created_at`, `departure_at`, `filters` e `itineraries`. Vem no máximo um trajeto por critério, e o mesmo caminho pode ganhar mais de um critério. Quando nenhum caminho funciona, por exemplo com as linhas fora do horário, a resposta continua 201 e `itineraries` vem vazio.

Cada trajeto tem `id`, `criterion`, `total_minutes`, `total_cost_cents`, `total_distance_meters`, `transfers` e `steps`. Cada etapa tem `sequence`, `mode`, `mode_name`, `color`, `line_code` (nulo sem linha), origem e destino com ids, nomes e coordenadas, `minutes`, `wait_minutes`, `transfer_minutes`, `cost_cents`, `fare_integrated`, `distance_meters`, `connection_id`, `has_stairs` e `accessible_for`, a lista dos tipos de necessidade que a etapa atende (a integração no início da etapa e as paradas em que a pessoa segue dentro do veículo não entram nessa conta). O `minutes` da etapa já inclui a espera e a integração. Quando `fare_integrated` é verdadeiro, a etapa embarcou por uma integração tarifária e `cost_cents` é só a diferença paga.

`GET /trajetos/{id}` devolve o trajeto com as etapas e mais três campos:

- `trip_search_id`: o id da busca;
- `search`: origem, destino, saída, filtros e `created_at` da busca. O detalhe calcula o horário em cada parada a partir de `departure_at` ou, numa busca para sair agora, de `created_at`;
- `geojson`: uma `FeatureCollection` com uma `LineString` por etapa, com coordenadas no formato `[longitude, latitude]`.

### Favoritos

`POST /favoritos` recebe `label`, de até 60 caracteres e sem repetir na mesma conta, e `stop_id`, de uma parada existente. Um favorito tem `id`, `label`, `stop_id`, `created_at` e os dados da parada: `name`, `address`, `latitude` e `longitude`.

## Erros

| Status | Quando | Corpo |
| --- | --- | --- |
| 401 | Rota que exige login, chamada sem login | `{"message": "Unauthenticated."}` |
| 404 | Id inexistente, ou de outra conta ou sessão | `{"message": ""}`, sem revelar se o registro existe |
| 419 | `POST`, `PUT` ou `DELETE` sem um token CSRF válido, como depois de a sessão expirar | `{"message": "CSRF token mismatch."}` |
| 422 | Dados inválidos | `message` com o primeiro erro e `errors` com as mensagens de cada campo, em português |
| 429 | Limite de requisições ultrapassado | `{"message": "Too Many Attempts."}` e o cabeçalho `Retry-After` |

O frontend mostra textos próprios, em português, para 401, 404, 419, 429 e 500. Exemplo de 422:

```json
{
  "message": "Escolha um destino diferente da origem. (e mais 1 erro)",
  "errors": {
    "destination_id": ["Escolha um destino diferente da origem."],
    "modes": ["Selecione ao menos um transporte."]
  }
}
```

## Limites de requisições

| Rotas | Limite |
| --- | --- |
| `/sessao`, `/catalogo`, `/paradas` e `/linhas` | 60 por minuto para cada conta ou sessão |
| `POST /buscas` | 30 por minuto para cada conta ou sessão; sem conta, também 120 por minuto por IP |
| `/entrar` e `/cadastrar` | 5 por minuto para cada e-mail, de qualquer IP, e 100 por minuto por IP |

As demais rotas não têm limite próprio. O IP é o do cliente, informado em `X-Forwarded-For` pelos proxies listados em `TRUSTED_PROXIES`: o Next.js e, no Docker, o nginx da entrada, que substitui o valor enviado pelo cliente. Sem conta, quem abre uma sessão nova a cada busca continua limitado pelo IP.

## Exemplo com curl

Uma busca de visitante de Boa Viagem (parada 1 na carga de demonstração) até Recife Antigo (parada 4):

```bash
API=http://localhost:8002/api/v1
TOKEN=$(curl -s -c cookies.txt -b cookies.txt -H 'Accept: application/json' "$API/sessao" | python3 -c 'import json, sys; print(json.load(sys.stdin)["data"]["csrf_token"])')
curl -s -c cookies.txt -b cookies.txt -X POST "$API/buscas" \
  -H 'Accept: application/json' -H 'Content-Type: application/json' -H "X-CSRF-TOKEN: $TOKEN" \
  -d '{"origin_id": 1, "destination_id": 4, "modes": ["bus", "metro", "taxi"]}'
```

O `cookies.txt` guarda a sessão. Com ele, as chamadas seguintes abrem a mesma busca em `$API/buscas/{id}` e seus trajetos em `$API/trajetos/{id}`.

## Testes

`tests/Feature/MobilityApiTest.php` cobre os endpoints: respostas, validações, mensagens em português, limites, cache e o acesso às buscas e aos favoritos de outra conta. O Laravel desliga a verificação de CSRF nos testes, então o 419 foi conferido chamando a API em funcionamento. `TripSearchPolicyTest`, `RouteAvailabilityTest` e `SpatialNetworkTest` cobrem quem pode abrir cada busca, o cálculo dos trajetos e as consultas do PostGIS. Os comandos estão no [README](../README.md#testes).
